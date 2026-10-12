require('dotenv').config();

const http = require('node:http');
const fs = require('node:fs/promises');
const path = require('node:path');
const { timingSafeEqual } = require('node:crypto');
const { Client, LocalAuth } = require('whatsapp-web.js');
const QRCode = require('qrcode');

const host = process.env.WHATSAPP_HOST || '127.0.0.1';
const port = Number(process.env.WHATSAPP_PORT || 3100);
const token = process.env.WHATSAPP_SERVICE_TOKEN || '';
const authPath = process.env.WHATSAPP_AUTH_PATH || './.wwebjs_auth';
const approved = process.env.WHATSAPP_PROVIDER_APPROVED === 'true';

if (token.length < 32) throw new Error('Set WHATSAPP_SERVICE_TOKEN to a secret of at least 32 characters.');

let qrValue = null;
let qrDataUrl = null;
let connected = false;
let account = null;
let connectionState = 'starting';
let reconnectTimer = null;
let shuttingDown = false;
let reconnectDelay = 5000;
let client;
let reconnecting = false;
let logoutRecoveryActive = false;

function createClient() {
  const instance = new Client({
    authStrategy: new LocalAuth({ dataPath: authPath }),
    puppeteer: {
      headless: true,
      args: ['--no-sandbox', '--disable-setuid-sandbox'],
      ...(process.env.PUPPETEER_EXECUTABLE_PATH ? { executablePath: process.env.PUPPETEER_EXECUTABLE_PATH } : {}),
    },
  });

  function watchBrowserErrors() {
    const page = instance.pupPage;
    if (!page || page.__whatsappDiagnosticsAttached) return;
    page.__whatsappDiagnosticsAttached = true;
    page.on('pageerror', error => console.error('WhatsApp Web page error:', error.message));
    page.on('console', message => {
      if (['error', 'warning'].includes(message.type())) {
        console.error(`WhatsApp Web ${message.type()}:`, message.text().slice(0, 500));
      }
    });
    page.on('requestfailed', request => {
      const url = new URL(request.url());
      if (['web.whatsapp.com', 'static.whatsapp.net', 'whatsapp.net'].some(host => url.hostname === host || url.hostname.endsWith(`.${host}`))) {
        console.error('WhatsApp Web network request failed:', `${url.hostname}${url.pathname}`, request.failure()?.errorText || 'unknown error');
      }
    });
  }

  instance.on('qr', qr => {
    watchBrowserErrors();
    clearTimeout(reconnectTimer);
    reconnectTimer = null;
    reconnecting = false;
    logoutRecoveryActive = false;
    qrValue = qr;
    qrDataUrl = null;
    QRCode.toDataURL(qr, { errorCorrectionLevel: 'M', margin: 2, width: 280 })
      .then(dataUrl => { if (qrValue === qr) qrDataUrl = dataUrl; })
      .catch(error => console.error('Could not render WhatsApp QR:', error.message));
    connected = false;
    connectionState = 'qr';
    console.log('A fresh WhatsApp QR is ready to scan.');
  });
  instance.on('authenticated', () => {
    watchBrowserErrors();
    qrValue = null;
    qrDataUrl = null;
    connectionState = 'authenticated';
    console.log('WhatsApp account authenticated; waiting for client readiness.');
    setTimeout(async () => {
      if (client !== instance || connected || !instance.pupPage || instance.pupPage.isClosed()) return;
      try {
        const canResume = await instance.pupPage.evaluate(() =>
          Boolean(window.AuthStore?.AppState?.hasSynced || window.AuthStore?.AppState?.get?.('hasSynced')) &&
          typeof window.onAppStateHasSyncedEvent === 'function'
        );
        if (!canResume || client !== instance || connected) return;
        console.error('WhatsApp readiness callback did not finish naturally; safely retrying it once.');
        await instance.pupPage.evaluate(() => window.onAppStateHasSyncedEvent());
      } catch (error) {
        console.error('WhatsApp readiness retry failed:', error.message);
      }
    }, 45000);
  });
  instance.on('ready', () => {
    clearTimeout(reconnectTimer);
    reconnectTimer = null;
    reconnectDelay = 5000;
    reconnecting = false;
    logoutRecoveryActive = false;
    connected = true;
    qrValue = null;
    qrDataUrl = null;
    connectionState = 'connected';
    account = instance.info?.wid?.user || null;
    console.log('WhatsApp session is ready.');
  });
  instance.on('auth_failure', message => {
    connected = false;
    qrValue = null;
    qrDataUrl = null;
    account = null;
    connectionState = 'reconnecting';
    console.error('WhatsApp authentication failed:', message);
    scheduleReconnect({ resetSession: true });
  });
  instance.on('disconnected', reason => {
    connected = false;
    qrValue = null;
    account = null;
    connectionState = 'reconnecting';
    console.error('WhatsApp disconnected:', reason);
    const loggedOut = ['LOGOUT', 'UNPAIRED', 'UNPAIRED_IDLE'].includes(String(reason).toUpperCase());
    if (loggedOut) {
      // whatsapp-web.js already handles its post_logout navigation and QR flow.
      // Give that flow a moment; only replace the client if it fails to emit a QR.
      logoutRecoveryActive = true;
      connectionState = 'generating_qr';
      reconnecting = true;
      clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(() => {
        reconnectTimer = null;
        void recoverLoggedOutClient(instance);
      }, 45000);
      return;
    }
    scheduleReconnect();
  });
  instance.on('change_state', state => {
    connectionState = String(state).toLowerCase();
    console.log('WhatsApp connection state:', state);
  });
  return instance;
}

async function recoverLoggedOutClient(previousClient) {
  if (!approved || shuttingDown || !logoutRecoveryActive) return;
  try {
    await previousClient.destroy().catch(() => {});
    await fs.rm(path.resolve(authPath, 'session'), { recursive: true, force: true, maxRetries: 4, retryDelay: 250 });
    client = createClient();
    await client.initialize();
  } catch (error) {
    console.error('WhatsApp logout recovery failed:', error.message);
    reconnectDelay = Math.min(reconnectDelay * 2, 300000);
    scheduleReconnect({ resetSession: true });
  }
}

function scheduleReconnect({ resetSession = false } = {}) {
  if (!approved || shuttingDown || reconnectTimer) return;
  reconnecting = true;
  connectionState = resetSession ? 'generating_qr' : 'reconnecting';
  reconnectTimer = setTimeout(() => {
    reconnectTimer = null;
    void (async () => {
      try {
        if (resetSession) {
          await client.destroy().catch(() => {});
          const sessionDir = path.resolve(authPath, 'session');
          await fs.rm(sessionDir, { recursive: true, force: true, maxRetries: 4, retryDelay: 250 });
          console.log('Cleared the logged-out WhatsApp session; generating a new QR.');
        }
        client = createClient();
        await client.initialize();
      } catch (error) {
        console.error('WhatsApp reconnect failed:', error.message);
        connectionState = 'reconnecting';
        reconnecting = false;
        reconnectDelay = Math.min(reconnectDelay * 2, 300000);
        scheduleReconnect({ resetSession });
      }
    })();
  }, reconnectDelay);
}

client = createClient();

function authorized(request) {
  const supplied = Buffer.from((request.headers.authorization || '').replace(/^Bearer\s+/i, ''));
  const expected = Buffer.from(token);
  return supplied.length === expected.length && timingSafeEqual(supplied, expected);
}

function respond(response, status, body) {
  response.writeHead(status, { 'content-type': 'application/json; charset=utf-8', 'cache-control': 'no-store' });
  response.end(JSON.stringify(body));
}

function sendAndWaitForServerAck(phone, chatId, message) {
  const normalize = value => String(value?._serialized || value || '').split('@')[0].replace(/\D/g, '');

  return new Promise((resolve, reject) => {
    let candidateId = null;
    let finished = false;
    const finish = (error, id) => {
      if (finished) return;
      finished = true;
      clearTimeout(timeout);
      client.off('message_create', onCreate);
      client.off('message_ack', onAck);
      error ? reject(error) : resolve(id);
    };
    const matches = sentMessage => {
      if (!sentMessage?.fromMe || sentMessage.body !== message) return false;
      const remote = sentMessage.to || sentMessage.id?.remote;
      const remoteNumber = normalize(remote);
      const remoteId = String(remote?._serialized || remote || '');
      return remoteId === chatId || remoteNumber === phone || !remoteNumber;
    };
    const onCreate = sentMessage => {
      if (!matches(sentMessage)) return;
      candidateId = sentMessage.id?._serialized || candidateId;
      console.log('WhatsApp outbound message created:', JSON.stringify({ hasId: Boolean(candidateId), ack: sentMessage.ack ?? null }));
      if (Number(sentMessage.ack) >= 1 && candidateId) finish(null, candidateId);
    };
    const onAck = (sentMessage, ack) => {
      if (!matches(sentMessage)) return;
      candidateId = sentMessage.id?._serialized || candidateId;
      console.log('WhatsApp outbound acknowledgement:', JSON.stringify({ hasId: Boolean(candidateId), ack }));
      if (ack >= 1 && candidateId) finish(null, candidateId);
      else if (ack < 0) finish(new Error('WhatsApp menolak pengiriman pesan.'));
    };
    const timeout = setTimeout(() => finish(new Error('WhatsApp belum mengonfirmasi pesan ke server.')), 30000);
    client.on('message_create', onCreate);
    client.on('message_ack', onAck);

    client.sendMessage(chatId, message, { sendSeen: false, waitUntilMsgSent: true })
      .then(sent => {
        const messageId = sent?.id?._serialized;
        // whatsapp-web.js can resolve after the send action is accepted even when
        // WhatsApp Web does not return the message model. The chat is validated
        // before this function is called, so a resolved send is an accepted send.
        finish(null, messageId || candidateId || 'accepted');
      })
      .catch(error => finish(error));
  });
}

async function readJson(request) {
  let body = '';
  for await (const chunk of request) {
    body += chunk;
    if (body.length > 16_384) throw new Error('Request body too large.');
  }
  return JSON.parse(body || '{}');
}

const server = http.createServer(async (request, response) => {
  if (!authorized(request)) return respond(response, 401, { error: 'Unauthorized' });
  if (request.method === 'GET' && request.url === '/status') {
    return respond(response, 200, {
      approved,
      connected: approved && connected,
      account: approved ? account : null,
      state: approved ? connectionState : 'disabled',
      qrReady: Boolean(qrValue),
      reconnecting: approved && reconnecting,
    });
  }
  if (request.method === 'GET' && request.url === '/qr') {
    return respond(response, 200, { qr: qrDataUrl });
  }
  if (request.method === 'POST' && request.url === '/messages') {
    if (!approved) return respond(response, 503, { error: 'WhatsApp provider is disabled pending approval.' });
    if (!connected) return respond(response, 503, { error: 'WhatsApp account is not connected.' });
    try {
      const body = await readJson(request);
      const phone = String(body.phone || '').replace(/\D/g, '');
      const message = String(body.message || '').trim();
      if (!/^[1-9][0-9]{7,14}$/.test(phone) || !message || message.length > 2000) {
        return respond(response, 422, { error: 'Invalid recipient or message.' });
      }
      const numberId = await client.getNumberId(phone);
      if (!numberId?._serialized) {
        return respond(response, 422, { error: 'Nomor penerima tidak terdaftar di WhatsApp.' });
      }
      const chat = await client.getChatById(numberId._serialized);
      if (!chat) return respond(response, 422, { error: 'WhatsApp tidak menemukan percakapan untuk nomor penerima.' });
      const messageId = await sendAndWaitForServerAck(phone, chat.id?._serialized || numberId._serialized, message);
      return respond(response, 202, { message_id: messageId });
    } catch (error) {
      console.error('WhatsApp request failed:', error.message);
      if (/detached frame|execution context was destroyed|target closed|session closed/i.test(error.message)) {
        connected = false;
        account = null;
        connectionState = 'reconnecting';
        scheduleReconnect();
        return respond(response, 503, { error: 'WhatsApp terputus saat mengirim pesan. Layanan sedang menyambungkan ulang perangkat.' });
      }
      return respond(response, 502, { error: 'WhatsApp belum dapat memproses pesan. Sistem akan mencoba lagi.' });
    }
  }
  return respond(response, 404, { error: 'Not found' });
});

server.listen(port, host, () => console.log(`WhatsApp service listening on http://${host}:${port}`));
if (approved) {
  client.initialize().catch(error => {
    console.error('WhatsApp client initialization failed:', error.message);
    scheduleReconnect();
  });
} else {
  console.log('WhatsApp provider is disabled pending approval.');
}

async function shutdown() {
  shuttingDown = true;
  clearTimeout(reconnectTimer);
  server.close();
  await client.destroy().catch(() => {});
  process.exit(0);
}
process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
process.on('unhandledRejection', reason => {
  const message = reason instanceof Error ? reason.message : String(reason);
  if (/ENOTEMPTY.*\.wwebjs_auth.*session|ENOTEMPTY.*session/i.test(message) && logoutRecoveryActive) {
    console.error('WhatsApp logout is still removing its local session; recovery will continue after cleanup.');
    return;
  }
  if (/Execution context was destroyed|Session closed\. Most likely the page has been closed|Target closed/i.test(message)) {
    console.error('WhatsApp Web closed a browser context during navigation/recovery:', message);
    if (logoutRecoveryActive && !reconnectTimer) scheduleReconnect({ resetSession: true });
    return;
  }
  throw reason;
});

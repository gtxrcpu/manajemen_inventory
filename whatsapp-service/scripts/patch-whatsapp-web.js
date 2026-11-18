const fs = require('node:fs');
const path = require('node:path');

const packageRoot = path.dirname(require.resolve('whatsapp-web.js/package.json'));
const clientFile = path.join(packageRoot, 'src', 'Client.js');
const utilsFile = path.join(packageRoot, 'src', 'util', 'Injected', 'Utils.js');
let source = fs.readFileSync(clientFile, 'utf8');

const originalStart = `        await exposeFunctionIfAbsent(
            this.pupPage,
            'onAppStateHasSyncedEvent',
            async () => {
                const authEventPayload =`;
const patchedStart = `        let appStateSetupRunning = false;
        let appStateSetupComplete = false;
        await exposeFunctionIfAbsent(
            this.pupPage,
            'onAppStateHasSyncedEvent',
            async () => {
                if (appStateSetupComplete || appStateSetupRunning) return;
                appStateSetupRunning = true;
                try {
                const authEventPayload =`;
const originalEnd = `                this.authStrategy.afterAuthReady();
            },
        );`;
const patchedEnd = `                this.authStrategy.afterAuthReady();
                appStateSetupComplete = true;
                } finally {
                    appStateSetupRunning = false;
                }
            },
        );`;

if (source.includes(patchedStart) && source.includes(patchedEnd)) {
  console.log('WhatsApp readiness callback guard is already applied.');
} else if (source.includes(originalStart) && source.includes(originalEnd)) {
  source = source.replace(originalStart, patchedStart).replace(originalEnd, patchedEnd);
  fs.writeFileSync(clientFile, source);
  console.log('Applied WhatsApp readiness callback guard for whatsapp-web.js 1.34.7.');
} else if (source.includes("const storeInjected = typeof window.WWebJS !== 'undefined';")) {
  console.log('WhatsApp readiness lifecycle guard is already handled by the upstream client.');
} else {
  throw new Error('Expected whatsapp-web.js 1.34.7 readiness callback was not found; review the package before applying this patch.');
}

let injectedUtils = fs.readFileSync(utilsFile, 'utf8');
const originalMessageLookup = '            .Msg.get(newMsgKey._serialized);';
const patchedMessageLookup = '            .Msg.get(newMsgKey._serialized || newMsgKey.$1);';

if (injectedUtils.includes(patchedMessageLookup)) {
  console.log('WhatsApp message ID compatibility fallback is already applied.');
} else if (injectedUtils.includes(originalMessageLookup)) {
  injectedUtils = injectedUtils.replace(originalMessageLookup, patchedMessageLookup);
  fs.writeFileSync(utilsFile, injectedUtils);
  console.log('Applied WhatsApp message ID compatibility fallback for WA Web 2.3000.x.');
} else {
  throw new Error('Expected whatsapp-web.js message lookup was not found; review the package before applying this patch.');
}

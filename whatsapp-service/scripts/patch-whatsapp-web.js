const fs = require('node:fs');
const path = require('node:path');

const packageRoot = path.dirname(require.resolve('whatsapp-web.js/package.json'));
const clientFile = path.join(packageRoot, 'src', 'Client.js');
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
} else {
  throw new Error('Expected whatsapp-web.js 1.34.7 readiness callback was not found; review the package before applying this patch.');
}

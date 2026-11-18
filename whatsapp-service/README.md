# Technolife WhatsApp Service

Private HTTP adapter used by Laravel's queued low-stock notifications. It binds to `127.0.0.1` by default and requires a shared bearer token of at least 32 characters.

## Setup after provider approval

1. Review the `whatsapp-web.js` operating risk and current `npm audit --omit=dev` report before installing or starting this service.
2. Install Node.js 18 or newer and the Chromium dependencies required by Puppeteer on the PC that will run the Laravel app.
3. Copy `.env.example` to `.env`. Set a random `WHATSAPP_SERVICE_TOKEN`; put the same token in Laravel's `.env`. Keep `.wwebjs_auth/` on a private persistent disk. These files and the linked WhatsApp session are not copied by Git.
4. Run `npm ci` inside `whatsapp-service`, then start the service with `npm start` (or `watchdog.ps1` on Windows).
5. Keep `WHATSAPP_PROVIDER_APPROVED=false` until the provider and dependency audit have been approved. After approval, set it to `true` in both Laravel's `.env` and `whatsapp-service/.env`, then restart the Node service. When enabled without a saved session, the service generates a QR for **Alert WhatsApp**.
6. Open **Alert WhatsApp** as Super Admin and scan the QR using the dedicated operational account.
7. Start a Laravel queue worker to process alert messages.

On Windows, `watchdog.ps1` starts both the WhatsApp service and Laravel's database queue worker at user logon. It restarts either process if it exits, restarts the WhatsApp service if it stops responding, and recycles it if WhatsApp remains offline without a QR for two minutes. It checks the authenticated local `/status` endpoint and uses a single-instance mutex so duplicate startup entries do not start multiple watchers. Put a hidden launcher for `watchdog.ps1` in the current user's Startup folder to enable automatic startup.

The QR/session is sensitive account access. Never expose this service beyond a private host/network or commit `.env` and `.wwebjs_auth/`.

## API

All endpoints require `Authorization: Bearer <WHATSAPP_SERVICE_TOKEN>`.

- `GET /status` — connection state and account identifier.
- `GET /qr` — QR image data URI while pairing is needed.
- `POST /messages` — JSON `{ "phone": "6281234567890", "message": "..." }`.

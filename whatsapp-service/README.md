# Technolife WhatsApp Service

Private HTTP adapter used by Laravel's queued low-stock notifications. It binds to `127.0.0.1` by default and requires a shared bearer token of at least 32 characters.

## Setup after provider approval

1. Review the `whatsapp-web.js` operating risk and current `npm audit --omit=dev` report before installing or starting this service.
2. Install Node.js 18 or newer and the Chromium dependencies required by Puppeteer.
3. Copy `.env.example` to `.env`, set a random `WHATSAPP_SERVICE_TOKEN`, and keep `.wwebjs_auth/` on a private persistent disk.
4. Run `npm ci`, then `npm start`.
5. Set the same service URL/token in Laravel. Keep `WHATSAPP_PROVIDER_APPROVED=false` until the provider and dependency audit have been approved.
6. Open **Alert WhatsApp** as Super Admin and scan the QR using the dedicated operational account.
7. Start a Laravel queue worker to process alert messages.

The QR/session is sensitive account access. Never expose this service beyond a private host/network or commit `.env` and `.wwebjs_auth/`.

## API

All endpoints require `Authorization: Bearer <WHATSAPP_SERVICE_TOKEN>`.

- `GET /status` — connection state and account identifier.
- `GET /qr` — QR image data URI while pairing is needed.
- `POST /messages` — JSON `{ "phone": "6281234567890", "message": "..." }`.

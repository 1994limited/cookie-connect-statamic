# Changelog

## 1.0.0 - 2026-10-18

### Added
- Cookiebot, Usercentrics, OneTrust, CookieYes, iubenda, Termly, CookieFirst, Cookie-Script and Custom, chosen per site under Tools → Cookie Connect, with IDs taken out of pasted snippets.
- Added to every front-end page by itself (before static caching): Consent Mode defaults, the platform, Google Tag Manager and Google Analytics 4 in the right order.
- The consent tag (`{{ consent:analytics }}`, `{{ consent categories="…" }}`, `placeholder`), `{{ consent:settings_link }}`, `{{ consent:declaration }}`, `{{ consent:placeholder }}`, `{{ consent:head }}`, `{{ consent:hashes }}`, the `consent` and `consent_tokens` modifiers, and `@consent` in Blade.
- Scripts pasted into the Control Panel, held back until consent.
- `{cookie_declaration}` and `{cookie_settings}` in content, and links to `#cookie-settings`.
- Addon settings and `config/cookie-connect.php`: environments, what happens elsewhere, per-site settings, a CSP nonce.

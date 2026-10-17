# JavaScript

Every page gets `window.CookieConnect`, whichever platform the site uses (or none), printed before the platform's own script. Use it instead of each platform's own API, and your code keeps working if the platform changes.

```js
CookieConnect.ready.then((consent) => {
  // { necessary: true, preferences: false, analytics: true, marketing: false, decided: true, provider: 'cookiebot' }
});

CookieConnect.get();                        // the same, now
CookieConnect.has('analytics');             // true or false
CookieConnect.has('analytics marketing');   // both needed

const stop = CookieConnect.on('change', (consent) => {
  // called at once (when known) and on every change
});

CookieConnect.openPreferences();
CookieConnect.refresh();   // after adding HTML with held-back content, e.g. over AJAX (usually found by itself)
```

`decided` is true once the visitor has chosen and false before. Some platforms can't say on page load (Usercentrics, iubenda, Termly); then it stays as it was.

`ready` resolves when the platform first answers, or after five seconds if it never does (blocked by an ad blocker, say), with nothing optional allowed.

The same reading is sent as an event and to the dataLayer:

```js
document.addEventListener('cookieconnect:change', (event) => console.log(event.detail));
document.addEventListener('cookieconnect:ready', (event) => console.log(event.detail));   // once
// dataLayer: { event: 'cookie_connect_update', cookie_connect: { … } }
```

## Debugging

Turn on **Debug in the browser console** under **Tools → Addons → Cookie Connect → Settings** to log each reading, and, for Usercentrics, any mapped category your account doesn't have.

# Troubleshooting

**The banner doesn't appear.** Check the box at the top of the Cookie Connect page:

- *Off in this environment*: it only loads in the environments under Tools → Addons → Cookie Connect → Settings.
- *Something needs fixing*: an ID is missing or doesn't look right.
- *Live*: open the page's source and look for `<!-- Cookie Connect -->` straight after `<head>`. If it's there, the platform itself isn't showing its banner: most tie their ID to your domains (Cookiebot domain group, OneTrust production script, CookieFirst site, Cookie-Script staging code), and some only show the banner to visitors in regions that need it.

**There's no `<!-- Cookie Connect -->` in the page.** It's added to front-end responses that are whole HTML pages with a `<head>`, on routes in Statamic's `statamic.web` middleware group. Check **Add to every page by itself** is on, and clear the static cache if pages were cached before Cookie Connect was installed.

**A held-back script never runs.** Check the category it needs is allowed (`CookieConnect.get()` in the browser console), and that your platform's ids match under Category mapping. Turn on debug to see each reading.

**Google Tag Manager loads twice.** Another addon or your layout adds it too. Cookie Connect doesn't add an ID that's already on the page, but it can't stop two different ones. Keep Google's tags in one place.

**Tag Assistant says the defaults were set twice.** Your platform or your Tag Manager container sets them too. Turn on the platform's option (Termly, CookieFirst) or **My Tag Manager container sets the defaults**.

**`Unknown cookie category "statistics"`.** Templates use Cookie Connect's categories (necessary, preferences, analytics, marketing), not the platform's.

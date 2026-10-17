# Choosing your platform

Choose the platform under **Tools → Cookie Connect**, then fill in its settings. Each ID field takes either the ID or the whole snippet your platform gives you to paste: Cookie Connect takes the ID out of it.

For every platform, Cookie Connect:

- prints the platform's script first in `<head>`, after the Consent Mode defaults (where the platform doesn't set them itself) and before Google's tags;
- marks content held back with the consent tag the way that platform expects (see [Holding back scripts](consent.md));
- reads the visitor's choice from the platform for [`window.CookieConnect`](javascript.md);
- opens the platform's preferences from [Cookie settings links](links.md), and prints its cookie declaration where it has one.

Each platform's details were checked against its own documentation on the date shown under its settings.

| Platform | You'll need | Consent Mode defaults set by | Declaration |
|---|---|---|---|
| [Cookiebot](#cookiebot) | Domain Group ID | Cookie Connect | Yes |
| [Usercentrics](#usercentrics) | Settings ID | Cookie Connect | Yes |
| [OneTrust](#onetrust) | Domain script ID | Cookie Connect | Yes |
| [CookieYes](#cookieyes) | Website key | Cookie Connect | Yes |
| [iubenda](#iubenda) | Embed code and site ID | iubenda | Yes, with the cookie policy ID |
| [Termly](#termly) | Website UUID | Termly (or Cookie Connect) | Yes, with the cookie policy ID |
| [CookieFirst](#cookiefirst) | API key | Cookie Connect (or CookieFirst) | Yes |
| [Cookie-Script](#cookie-script) | Banner ID | Cookie Connect | Yes |
| [Custom](#custom) | Your platform's code | Cookie Connect (or your platform) | No |

## Category mapping

Cookie Connect has four categories: **necessary**, **preferences**, **analytics** and **marketing**. Each platform has its own names for them, filled in for you. If you've renamed or added categories in your platform (common with OneTrust and Usercentrics), open **Category mapping** and enter your platform's ids. A mapping can list several ids, separated by commas, which are all needed.

## Cookiebot

- **Domain Group ID**: Cookiebot → Settings → Your scripts.
- **Hosting**: choose EU only if your account uses Cookiebot's EU CDN (`consent.cookiebot.eu`).
- **Automatic cookie blocking**: Cookiebot blocks known scripts by itself. Cookie Connect marks its own scripts so the blocker leaves them alone.
- **IAB TCF 2.2**: only if you show ads that need it.

Categories: preferences → `preferences`, analytics → `statistics`, marketing → `marketing`. Add every domain the site runs on, staging too, to the domain group in Cookiebot.

## Usercentrics

- **Settings ID**: at the top of the Admin Interface, or under Implementation → Script Tag. Choose **Ruleset ID** if you use geolocation rulesets.
- **Automatic blocking**: turn on only if Auto blocking is on in Usercentrics (it depends on your plan).
- **Show the draft configuration**: previews unpublished changes. For staging only.

Usercentrics blocks scripts by service name, not category, so Cookie Connect holds back consent-tag content itself and releases it from Usercentrics' category choices. Usercentrics has no Analytics category out of the box, so analytics follows your **marketing** category; if you've added one, enter its id under Category mapping. Consent Mode updates come from the Google services you've added in Usercentrics.

## OneTrust

- **Domain script ID**: Cookie Consent → Scripts → your domain. The production ID only works on its own domain; the test ID (ending `-test`) works anywhere, so use it on staging (see [per-site settings in config](configuration.md#per-site-settings-in-config)).
- **CDN**: OneTrust or CookiePro.
- **Automatic blocking**: only if "Enable Automatic Blocking of Cookies" is on when you publish the script.
- **Use the page's language**: shows the banner in the page's language.

Categories default to OneTrust's own group IDs: preferences → `C0003`, analytics → `C0002`, marketing → `C0004`. Older accounts use `3`, `2`, `4`; check yours under Categorizations → Categories. OneTrust asks for Google's tags before its own script, so Cookie Connect puts them there. Turn on Google Consent Mode in each geolocation rule in OneTrust.

## CookieYes

- **Website key**: Advanced Settings → Get Installation Code.

CookieYes marks only scripts that load a file (`data-cookieyes`), one category each; Cookie Connect holds back everything else itself. In advanced Consent Mode Google's tags go before CookieYes, in basic mode after it, as CookieYes asks. Turn on **Support GCM** in CookieYes so it sends Consent Mode updates.

## iubenda

- **Embed code**: Privacy Controls and Cookie Solution → Embed.
- **Site ID**: the number in your iubenda dashboard's address for the site. It loads iubenda's Consent Mode script.
- **Cookie policy ID**: for the declaration.
- **Automatic blocking**: only if it's on in iubenda.

iubenda sets Consent Mode defaults itself and asks sites not to add their own, so Cookie Connect doesn't. Turn on per-category consent in iubenda, or every category is accepted or declined together. iubenda's Experience purpose (3) has no Cookie Connect category: to require it with preferences, set preferences to `2,3` under Category mapping.

## Termly

- **Website UUID**: Consent Banner → Install.
- **Auto Blocker**: on in Termly's own install code.
- **Termly handles Google Consent Mode**: Termly sets the defaults and sends updates when Google Consent Mode is on in its Consent Settings. Turn this off to let Cookie Connect do it instead.
- **Cookie policy ID**: the `data-id` in your cookie policy's code snippet (not the website UUID).

## CookieFirst

- **API key**: Sites → Settings → Your embed script.
- **Domain**: the domain as it's set up in CookieFirst, without `www.`. Leave it empty to use the site's own.
- **CookieFirst sets Consent Mode defaults**: only if you've set defaults in CookieFirst's dashboard.

CookieFirst reloads the page when a visitor changes their choice.

## Cookie-Script

- **Banner ID**: your banner → Install → Script tag.
- **Staging code**: loads Cookie-Script's staging banner (paid plans), which only works on the staging domain set up in Cookie-Script.
- **Cookie report ID**: for the declaration, if it isn't the banner ID.

Turn on Google Consent Mode v2 in Cookie-Script so it sends updates.

## Custom

For any other platform:

1. **Consent platform code**: the code your platform gives you for the head. It's printed first, as it is.
2. **Tell Cookie Connect about consent**: JavaScript that calls `CookieConnect.set({ preferences, analytics, marketing })` from your platform's consent callback.
3. **Open the preferences**: JavaScript that opens your platform's cookie settings.
4. Say whether your platform sets Consent Mode defaults or sends updates itself.

```js
// Tell Cookie Connect about consent
window.addEventListener('my-cmp-consent', function (e) {
  CookieConnect.set({ preferences: e.detail.functional, analytics: e.detail.stats, marketing: e.detail.ads });
});
```

Content in the consent tag is held back by Cookie Connect itself and runs as soon as `set()` allows it.

# Google tags and Consent Mode

Google Consent Mode v2 tells Google's tags what each visitor allowed. The order matters: the defaults (everything denied) must be set before any Google tag runs, and the consent platform has to load before Google's tags so it can send the visitor's choice.

Cookie Connect prints them in this order, first thing in `<head>`:

1. The Consent Mode defaults, unless your platform sets them (iubenda, and Termly or CookieFirst when set up to).
2. Cookie Connect's own script.
3. Your consent platform.
4. Google Tag Manager and Google Analytics 4, if you add them here (OneTrust and CookieYes in advanced mode ask for these before the platform, so they go there).

## Adding Google's tags

Under **Google tags and Consent Mode**:

- **Google Tag Manager container ID**: `GTM-…`. You can paste the whole snippet.
- **Google Analytics 4 measurement ID**: `G-…`. Leave it empty if Analytics runs through Tag Manager.

Add them here rather than in your templates, so they load after the consent platform. If you add them in a template too, they'll load twice.

## Consent Mode settings

- **Google Consent Mode v2**: on by default. Turn it off only if you use no Google tags.
- **Mode**: *Advanced* loads Google's tags straight away; they send cookieless pings until the visitor allows them, which lets Google model what it misses. *Basic* loads Google Analytics only once analytics is allowed. (Tag Manager always loads; in basic mode, set each tag's consent settings in Tag Manager.)
- **Ask for consent**: *Everywhere*, or *Only in the EEA, the UK and Switzerland*, where the defaults are denied and granted elsewhere.
- **My Tag Manager container sets the defaults**: turn on if your container has a consent platform template on the Consent Initialization trigger, so the defaults aren't set twice.
- **Redact ad data when ads are declined** (`ads_data_redaction`, on), **Pass ad click information through URLs** (`url_passthrough`, off).
- **Wait for the consent platform**: how long Google's tags wait for the platform's answer. Empty uses the platform's own recommendation (500 or 2000 milliseconds).

The defaults deny `ad_storage`, `ad_user_data`, `ad_personalization`, `analytics_storage`, `functionality_storage` and `personalization_storage`, and grant `security_storage`. Each category grants:

| Category | Consent Mode types |
|---|---|
| necessary | `security_storage` |
| preferences | `functionality_storage`, `personalization_storage` |
| analytics | `analytics_storage` |
| marketing | `ad_storage`, `ad_user_data`, `ad_personalization` |

Most platforms send the updates themselves once Google Consent Mode is turned on in their settings (see [Choosing your platform](platforms.md)). For Custom (and Termly with Cookie Connect handling Consent Mode), Cookie Connect sends them.

In Tag Manager, a Custom Event trigger on `cookie_connect_update` fires after each update.

## Other addons and templates

If another addon or your layout already adds Google Tag Manager or gtag.js, keep Google's tags in one place. When Cookie Connect adds itself to pages (the default), it never adds a tag whose ID a script on the page already loads, whoever put it there, so the same container never loads twice; it adds a comment saying so. If you place `{{ consent:head }}` yourself, it can't see the rest of the page: keep Google's tags in one place. Its Consent Mode defaults and your platform still come first, because Cookie Connect goes straight after `<head>`.

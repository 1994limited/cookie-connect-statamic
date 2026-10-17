# Cookie settings links and the declaration

## For editors

Nothing here needs a template change.

- **A Cookie settings link**: link to `#cookie-settings` from anywhere (a navigation, a footer, a Bard field). Clicking it opens your platform's preferences. A link to another page's address ending in `#cookie-settings` (`https://example.com/#cookie-settings`, in an email, say) opens them when that page loads.
- **The cookie declaration**: type `{cookie_declaration}` on its own line in a Bard or Markdown field. Your platform's cookie list appears there. Platforms without a declaration (Custom) show nothing.
- **A link in a sentence**: type `{cookie_settings}`, as in "You can change your mind at any time: {cookie_settings}." It becomes a Cookie settings link.

The tokens are swapped as the page is sent, so they work in any field. (If **Add to every page by itself** is off, use the `consent_tokens` modifier on the field instead.)

## For templates

```antlers
{{ consent:settings_link }}
{{# <button type="button" class="cookie-connect-settings" data-cookie-connect-open>Cookie settings</button> #}}

{{ consent:settings_link text="Change my cookie choices" class="footer-link" }}
{{ consent:settings_link as="a" }}

{{ consent:declaration }}

{{ content | consent_tokens }}
```

<details>
<summary>Blade</summary>

```blade
<s:consent:settings_link />
<s:consent:settings_link text="Change my cookie choices" class="footer-link" />
<s:consent:settings_link as="a" />

<s:consent:declaration />

{!! Statamic::modify($content)->consentTokens() !!}
```

</details>

Any element with `data-cookie-connect-open` opens the preferences. Where a platform opens its preferences from its own class (CookieYes, Termly, iubenda, Cookie-Script), the link gets that class too and the platform opens them.

"Cookie settings" and the placeholder text are translatable strings.

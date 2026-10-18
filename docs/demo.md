# The demo

Dog-ear Books is a made-up bookshop for trying Cookie Connect: a video held back until marketing cookies are allowed, an analytics script, a cookie policy page using `{cookie_declaration}`, and a Cookie settings link in the footer. It comes with a pretend consent platform (`/fake-cmp.js`), so you can try everything with the Custom platform before setting up a real one.

It's at [github.com/1994limited/cookie-connect-statamic-demo](https://github.com/1994limited/cookie-connect-statamic-demo). To run it:

```bash
git clone https://github.com/1994limited/cookie-connect-statamic-demo.git
cd cookie-connect-statamic-demo
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan demo:users
php artisan demo:content
php artisan serve
```

Sign in at `/cp` as `admin@example.com` with the password `password` (for a local copy only). See [its README](https://github.com/1994limited/cookie-connect-statamic-demo#readme) for what to try.

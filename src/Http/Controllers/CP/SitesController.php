<?php

namespace NineteenNinetyFour\CookieConnect\Http\Controllers\CP;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use NineteenNinetyFour\CookieConnect\Blueprints\SiteBlueprint;
use NineteenNinetyFour\CookieConnect\Core\Output\Runtime;
use NineteenNinetyFour\CookieConnect\Core\Providers\Providers;
use NineteenNinetyFour\CookieConnect\Output;
use NineteenNinetyFour\CookieConnect\ServiceProvider;
use NineteenNinetyFour\CookieConnect\Settings;
use NineteenNinetyFour\CookieConnect\SiteSettings;
use Statamic\CP\PublishForm;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Site;
use Statamic\Facades\StaticCache;
use Statamic\Http\Controllers\CP\CpController;
use Statamic\Sites\Site as SiteObject;

/**
 * Tools → Cookie Connect: one page per site.
 */
class SitesController extends CpController
{
    public function __construct(Request $request, protected SiteSettings $sites, protected Output $output, protected Settings $settings)
    {
        parent::__construct($request);
    }

    public function edit(?string $site = null)
    {
        $this->authorize(ServiceProvider::PERMISSION);

        $site = $this->site($site);
        $renderer = $this->output->renderer($site);
        $blueprint = new SiteBlueprint(Providers::default());

        return PublishForm::make($blueprint->make([
            'status' => $this->status($site, $renderer->state(), $renderer->provider()?->name),
            'preview' => '<pre class="text-xs whitespace-pre-wrap break-all max-h-96 overflow-auto p-3 bg-gray-100 dark:bg-gray-900 rounded">'.e(str_replace(Runtime::source(), '/* '.__('Cookie Connect’s script').' */', $renderer->head())).'</pre>',
        ], $this->sites->overridden($site), $renderer->provider()))
            ->icon(ServiceProvider::ICON)
            ->title(Site::multiEnabled() ? __('Cookie Connect').': '.$site->name() : __('Cookie Connect'))
            ->values($blueprint->toForm(array_replace_recursive($this->sites->stored($site), $this->sites->overrides($site))))
            ->submittingTo(cp_route('cookie-connect.update', $site->handle()));
    }

    public function update(Request $request, string $site): array
    {
        $this->authorize(ServiceProvider::PERMISSION);

        $site = $this->site($site);
        $providers = Providers::default();
        $blueprint = new SiteBlueprint($providers);

        $data = $blueprint->fromForm($request->all());
        [$saved, $problems] = $this->sites->check($site, $data);

        if ($problems !== []) {
            $provider = $providers->get($saved['provider'] ?? null);
            $errors = [];

            foreach ($problems as $path => $message) {
                $errors[$blueprint->errorKey($path, $provider)][] = $message;
            }

            throw ValidationException::withMessages($errors);
        }

        $this->sites->save($site, $saved);

        if (config('statamic.static_caching.strategy')) {
            StaticCache::flush();
        }

        return ['saved' => true];
    }

    protected function site(?string $handle): SiteObject
    {
        $site = $handle === null ? Site::default() : Site::get($handle);

        if (! $site) {
            throw new NotFoundHttpException;
        }

        return $site;
    }

    protected function status(SiteObject $site, string $state, ?string $platform): string
    {
        $message = match ($state) {
            'live' => __(':platform is live on this site.', ['platform' => $platform]),
            'off' => __('Cookie Connect is off in this environment (:environment).', ['environment' => app()->environment()]).' '.[
                'block' => __('Content that needs consent stays blocked here.'),
                'run' => __('Content that needs consent runs here as if it had been accepted, so you can test your tags.'),
                'omit' => __('Content that needs consent is left out here.'),
            ][$this->settings->whenOff()],
            'none' => __('Choose your consent platform below. Until then nothing is loaded and content that needs consent stays blocked.'),
            'unknown' => __('This version of Cookie Connect doesn’t know the platform you chose.'),
            default => __('Something needs fixing before :platform is loaded.', ['platform' => $platform]),
        };

        // Inline, translucent colours: they read in light and dark mode alike.
        $colour = match ($state) {
            'live' => '34, 197, 94',
            'off', 'none' => '234, 179, 8',
            default => '239, 68, 68',
        };

        $sites = '';

        if (Site::multiEnabled()) {
            $links = Site::all()->map(fn (SiteObject $s) => $s->handle() === $site->handle()
                ? '<strong>'.e($s->name()).'</strong>'
                : '<a class="underline" href="'.e(cp_route('cookie-connect.edit', $s->handle())).'">'.e($s->name()).'</a>')->implode(' · ');
            $sites = '<p class="mt-2 text-sm">'.e(__('Sites')).': '.$links.'</p>';
        }

        $settings = $state === 'off'
            ? ' <a class="underline" href="'.e(cp_route('addons.settings.edit', 'cookie-connect-statamic')).'">'.e(__('Change where it runs')).'</a>'
            : '';

        return '<div style="border-inline-start: 4px solid rgb('.$colour.'); background: rgba('.$colour.', .12); border-radius: 6px; padding: 12px 16px"><p>'.e($message).$settings.'</p>'.$sites.'</div>';
    }
}

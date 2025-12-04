<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Enums\MaxWidth;
use Filament\FontProviders\GoogleFontProvider;
use App\Filament\Pages\Dashboard;
use App\Http\Middleware\InertiaExternalRedirect;
use Filament\Facades\Filament;
use BladeUI\Icons\Factory as IconFactory;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Illuminate\Support\Facades\Vite;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Blade;

class AdminPanelProvider extends PanelProvider
{
    public function boot()
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                'Taakconfigurator',
                'Instellingen',
            ]);
        });

        // Register a custom icon path(folder) for use with the Icon Picker Filament plugin
        app(IconFactory::class)->add('taskicons', [
            'path' => resource_path('images/icons'),
            'prefix' => 'az',
        ]);

        FilamentView::registerRenderHook('panels::body.end', fn(): string => Blade::render("@vite('resources/js/src/filament/app.js')"));
        FilamentAsset::register([Js::make('tiptap-custom-extension-scripts', Vite::asset('resources/js/src/filament/tiptap/extensions.js'))->module()], 'awcodes/tiptap-editor');
    }
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('adm')
            ->path('adm')
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => '#008F92',
                'gray' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                InertiaExternalRedirect::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->spa()
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3.7rem')
            ->homeUrl('/')
            ->favicon(asset('images/favicon-32x32.png'))
            ->viteTheme('resources/css/filament.css');
    }
}

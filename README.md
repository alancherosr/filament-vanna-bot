# Vanna Intergration 

Filament Vanna Bot is a filament plugin that allow you to use Vanna.ai API within Filament Application.

## Feature

- Integrate with Vanna.ai
- Easy to Setup
- Shortcut allows control of panel in more convenient way
- Support for dark mode


## Installation

First, you can install the package via composer:

```bash
composer require alancherosr/filament-vanna-bot
```

## Publish Config Files

Next, you can publish the config files with:

```bash
php artisan vendor:publish --tag="filament-vanna-bot-config"
```

This will create a `config/filament-vanna-bot.php` configuration file in your project, which you can modify to your needs using environment variables:

```
OPENAI_API_KEY=sk-...
```

Go to admin panel, you will see a small icon in gray color on the bottom-right corner of every page. Click the icon, you will then see a chat panel. And now you can talk to OpenAI ChatGPT with the chat panel. Click the small icon again, the chat panel will become hidden again.

By optionally adding the `OPENAI_PROXY` to `.env` file, you could use http proxy to connect ChatGPT. Example as below:

```
OPENAI_PROXY=127.0.0.1:8080
```

## Views

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-vanna-bot-views"
```

## More

1. By defult, there is a small chatgpt icon on bottom-right corner of admin panel after the package installed. You could hide the icon by setting `enable` to `false` in `config/filament-vanna-bot.php` files:

```php

    'enable' => false,

```

> This may require you publish [config files](#publish-config-files).

2. You could also render it in [Panel Configuration](https://laravel-filament.cn/docs/en/3.x/panels/configuration#render-hooks) like this：

```php
public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->renderHook(
            'panels::body.end',
            fn (): string => auth()->check() ? Blade::render('@livewire(\'livewire-ui-modal\')') : '',
        );
}
```

> Set `enable` in `config/filament-vanna-bot.php` files, if you like to render it in [Panel Configuration](https://laravel-filament.cn/docs/en/3.x/panels/configuration#render-hooks).

3. Alternatively, you can add it to any blade file within livewire page if you like to do it manually:

```blade
<body>

    ...

    @livewire('filament-vanna-bot')
</body>
```

> This is work for all livewire page in any Laravel Project, not just Filament. Please also make sure Tailwind CSS and Livewire were imported properly while use in other Laravel Project. And please note that you should also set `enable` to be `false` in `config/filament-vanna-bot.php` files while use this in non-Filament Project.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Andres Lancheros](https://github.com/alancherosr)
- [All Contributors](../../contributors)

## Support

Any Problem please email: alancherosr@gmail.com.

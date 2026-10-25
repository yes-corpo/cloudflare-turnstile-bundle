# Cloudflare Turnstile Bundle

Protects Symfony forms with [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/):
a `TurnstileType` form type renders the widget, and a `Turnstile` constraint verifies the token
with Cloudflare when the form is submitted.

Requires PHP 8.1+ and Symfony 6.4 or 7.x.

## Installation

```bash
composer require yes-corpo/cloudflare-turnstile-bundle
```

Without Symfony Flex, enable the bundle in `config/bundles.php`:

```php
YesCorpo\Bundle\CloudflareTurnstileBundle\CloudflareTurnstileBundle::class => ['all' => true],
```

## Configuration

```yaml
# config/packages/cloudflare_turnstile.yaml
cloudflare_turnstile:
    site_key: '%env(TURNSTILE_SITE_KEY)%'
    secret_key: '%env(TURNSTILE_SECRET_KEY)%'
```

In development and tests, use the
[Cloudflare test keys](https://developers.cloudflare.com/turnstile/troubleshooting/testing/).

## Usage

```php
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;

$builder->add('captcha', TurnstileType::class);
```

The form theme is registered automatically: `{{ form_widget(form.captcha) }}` renders the widget.
The error is displayed by `{{ form_errors(form) }}`, see [error bubbling](docs/usage.md#error-bubbling).

## Documentation

- [Usage](docs/usage.md): form options, error messages, translations, how the verification works.

## Development

```bash
composer install
composer test
```

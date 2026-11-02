# Cloudflare Turnstile Bundle

Protects Symfony forms with [Cloudflare Turnstile](https://developers.cloudflare.com/turnstile/):
a `TurnstileType` form type renders the widget, and a `Turnstile` constraint verifies the token
with Cloudflare when the form is submitted.

| Symfony | PHP  |
|---------|------|
| 6.4     | 8.1+ |
| 7.4     | 8.2+ |
| 8.x     | 8.4+ |

## Installation

The bundle has no Flex recipe and both keys are required, so create the configuration first,
otherwise the `cache:clear` run by `composer require` fails:

```yaml
# config/packages/cloudflare_turnstile.yaml
cloudflare_turnstile:
    site_key: '%env(TURNSTILE_SITE_KEY)%'
    secret_key: '%env(TURNSTILE_SECRET_KEY)%'
```

```dotenv
# .env
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
```

In development and tests, use the
[Cloudflare test keys](https://developers.cloudflare.com/turnstile/troubleshooting/testing/).

Then install the bundle:

```bash
composer require yes-corpo/cloudflare-turnstile-bundle
```

Without Symfony Flex, enable the bundle in `config/bundles.php`:

```php
YesCorpo\Bundle\CloudflareTurnstileBundle\CloudflareTurnstileBundle::class => ['all' => true],
```

## Usage

```php
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;

$builder->add('captcha', TurnstileType::class);
```

The form theme is registered automatically: `{{ form_widget(form.captcha) }}` renders the widget.
The error is displayed by `{{ form_errors(form) }}`, see [error bubbling](docs/usage.md#error-bubbling).

## Documentation

- [Usage](docs/usage.md): form options, error messages, translations, how the verification works.
- [Trolls](docs/troll.md): optional humorous fake CAPTCHAs shown before the real challenge.

## Development

```bash
composer install
composer test
```

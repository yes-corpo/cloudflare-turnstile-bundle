# Usage

## Form options

| Option                  | Default  | Description                                                                                                                                                         |
|-------------------------|----------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `turnstile_theme`       | `'auto'` | Widget theme: `auto`, `light` or `dark`.                                                                                                                            |
| `turnstile_load_script` | `true`   | Adds the Turnstile `<script>` tag. Set it to `false` when `api.js` is already loaded, for example with several widgets on the same page.                            |
| `constraints`           | `[]`     | Additional constraints. The `Turnstile` constraint is always added.                                                                                                 |
| `attr`                  | `[]`     | HTML attributes of the widget `div`, for example `{'data-size': 'compact'}` or any [Turnstile `data-*` option](https://developers.cloudflare.com/turnstile/get-started/client-side-rendering/#configurations). |

The field is not mapped and has no label.

## Error messages

| Property                | Default message                                        | When                                                                      |
|-------------------------|--------------------------------------------------------|---------------------------------------------------------------------------|
| `message`               | `The captcha is invalid.`                              | Cloudflare rejects the token (missing, expired, already used…).           |
| `technicalErrorMessage` | `The captcha could not be verified, please try again.` | The call to Cloudflare fails (network, unreadable response). The submission is rejected. |

To change them, pass your own constraint:

```php
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\Turnstile;

$turnstile = new Turnstile();
$turnstile->message = 'Please complete the captcha.';

$builder->add('captcha', TurnstileType::class, ['constraints' => $turnstile]);
```

## Translations

English is the source language. The bundle ships translations for:
Arabic (`ar`), Chinese (`zh_CN`), Dutch (`nl`), English (`en`), French (`fr`), German (`de`),
Hindi (`hi`), Italian (`it`), Japanese (`ja`), Korean (`ko`), Polish (`pl`), Portuguese (`pt`),
Russian (`ru`), Spanish (`es`), Turkish (`tr`) and Ukrainian (`uk`).

The messages use the `validators` domain. Override a message, or add a language, in the application
`translations/` directory, for example `translations/validators.sv.xlf`, with the English message
as the key.

## How it works

1. The widget is rendered in a `div.cf-turnstile`. Turnstile adds a hidden input named after the
   Symfony field (`data-response-field-name`).
2. On submit, `TurnstileValidator` sends the token, the secret key and the client IP to
   `https://challenges.cloudflare.com/turnstile/v0/siteverify`.
3. A violation is added unless the response contains `success: true`.

### Error bubbling

`TurnstileType` extends `HiddenType`, so `error_bubbling` is `true`: the error is attached to the
parent form and displayed by `{{ form_errors(form) }}`, not by `{{ form_errors(form.captcha) }}`.
Set `'error_bubbling' => false` to keep it on the field.

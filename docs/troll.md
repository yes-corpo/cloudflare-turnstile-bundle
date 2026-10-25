# Trolls

A troll is a humorous fake CAPTCHA displayed, now and then, before the real Turnstile widget:
a checkbox that runs away, a grid asking to select "the images containing hope", a loader stuck
at 99 %… Once the user completes it, the real widget appears.

Trolls are purely cosmetic:

- the real widget is always rendered, unchanged, and keeps loading while the troll is displayed;
- the server-side verification is the same with or without a troll;
- the fake controls are `<button type="button">` elements without `name`: they never change the
  submitted data and never submit the form;
- without JavaScript, or if the troll assets are missing, only the real widget is displayed.

## Configuration

Trolls are disabled by default.

```yaml
cloudflare_turnstile:
    troll:
        enabled: true
        probability: 0.01        # between 0 and 1
        cooldown: 86400          # seconds without another troll once one has been displayed
        excluded_routes: [checkout_payment]
        excluded_forms: [contact]   # names of the root forms
```

A single troll is displayed per page, and never twice in a row: when a troll is displayed,
`base.js` sets a `cf_turnstile_troll` cookie that expires after `cooldown` seconds.

To exclude a single field, use the `turnstile_troll` form option:

```php
$builder->add('captcha', TurnstileType::class, ['turnstile_troll' => false]);
```

### Choosing the trolls

Every registered troll, from the bundle or from the application, is enabled by default.

```yaml
cloudflare_turnstile:
    troll:
        disabled_trolls: [fleeing_checkbox]
        # or an explicit list:
        # trolls: [hope_grid, reverse_captcha]
```

| Name                | Description                                                         |
|---------------------|---------------------------------------------------------------------|
| `fleeing_checkbox`  | The checkbox runs away from the mouse for 3 s, then gives up.       |
| `hesitant_checkbox` | The checkbox unchecks itself once: "you hesitated, suspicious".     |
| `hope_grid`         | Select all the images containing hope. Every answer is right.       |
| `stuck_loader`      | The loader gets stuck at 99 %, then "you are 73 % human".           |
| `reverse_captcha`   | Prove that you are a robot.                                         |

An unknown name in `trolls`, `disabled_trolls` or `assets` is a configuration error.

### Forcing a troll in development

In the `dev` environment only, add `?_turnstile_troll=<name>` (or `?_turnstile_troll=random`) to
the URL. It ignores `enabled`, `probability`, `cooldown` and `disabled_trolls`, but not the
exclusions.

## Behaviour

- Like a real CAPTCHA, the troll has to be completed. 15 seconds after the first attempt, if the
  user is still stuck, a "Fine, OK, you may go" button appears.
- The troll is not displayed with `forced-colors` (high contrast mode), and the motion-based trolls
  (`fleeing_checkbox`) are not displayed with `prefers-reduced-motion`. Animations are disabled
  with `prefers-reduced-motion`.
- The fleeing checkbox never runs away from the keyboard or a touch screen.
- When the troll ends, the keyboard focus moves to the real widget.

There is no reliable way to detect a screen reader. The trolls are buttons with accessible names
and their messages are announced (`aria-live`), so they can be completed with a screen reader, but
if this matters for your audience, disable the trolls on the forms concerned.

## Assets

Each page with a troll loads the base assets and the assets of the displayed troll only:

```
bundles/cloudflareturnstile/troll/base.css
bundles/cloudflareturnstile/troll/base.js
bundles/cloudflareturnstile/troll/<name>.css   (when the troll has one)
bundles/cloudflareturnstile/troll/<name>.js
```

They are installed by `bin/console assets:install` (run by Symfony Flex after `composer install`).

Every path can be replaced, or set to `false` to load nothing:

```yaml
cloudflare_turnstile:
    troll:
        stylesheet: 'css/troll.css'          # replaces base.css
        script: 'bundles/cloudflareturnstile/troll/base.js'
        assets:
            hope_grid:
                stylesheet: 'css/hope_grid.css'
            stuck_loader:
                script: false
```

The troll stylesheets are loaded next to the widget, after the stylesheets of the page `<head>`.
To restyle the trolls, replace `base.css` rather than overriding its rules.

## Overriding a template

As for any bundle, put the template in `templates/bundles/CloudflareTurnstileBundle/`, for example
`templates/bundles/CloudflareTurnstileBundle/troll/hope_grid.html.twig`. To keep the original
blocks, extend `@!CloudflareTurnstile/troll/hope_grid.html.twig`.

## Adding a troll

### In the application

1. A class implementing `TrollInterface`. With autoconfiguration, it is registered and enabled
   automatically.

   ```php
   namespace App\Troll;

   use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollInterface;

   class MathTroll implements TrollInterface
   {
       public static function getName(): string
       {
           return 'math';
       }

       public function getTemplate(): string
       {
           return 'troll/math.html.twig';
       }

       public function getStylesheet(): ?string
       {
           return null;
       }

       public function getScript(): ?string
       {
           return 'troll/math.js';   // asset path, in public/
       }

       public function usesMotion(): bool
       {
           return false;
       }
   }
   ```

2. The template extends the layout, which provides the frame, the message and the skip button.
   Texts used by the script go in the `texts` block, as `data-*` attributes.

   ```twig
   {% extends '@CloudflareTurnstile/troll/_layout.html.twig' %}

   {% block body %}
       <p class="cf-troll__title">{{ 'troll.math.title'|trans }}</p>
       <div class="cf-troll__actions">
           <button type="button" class="cf-troll__action" data-math-answer>4</button>
           <button type="button" class="cf-troll__action" data-math-answer>22</button>
       </div>
   {% endblock %}

   {% block texts %}data-done="{{ 'troll.math.done'|trans }}"{% endblock %}
   ```

3. The script registers the behaviour. This syntax works whether it runs before or after `base.js`:

   ```js
   (window.CfTurnstileTroll = window.CfTurnstileTroll || []).push(['math', function (troll) {
       troll.root.querySelectorAll('[data-math-answer]').forEach(function (answer) {
           troll.on(answer, 'click', function () {
               troll.done(troll.text('done'));
           });
       });
   }]);
   ```

A behaviour pushed with the name of a bundle troll replaces the bundle one.

### In the bundle

1. A `final` class extending `AbstractTroll` in `src/Troll/`, added to `BUILTIN_TROLLS` in
   `CloudflareTurnstileBundle`. Override `getStylesheet()` to return `self::asset('css')` when the
   troll has a stylesheet, and `usesMotion()` when it relies on motion.
2. The template `templates/troll/<name>.html.twig`, with `|trans({}, 'cloudflare_turnstile')`.
3. The texts in every `translations/cloudflare_turnstile.<locale>.xlf` file.
4. `public/troll/<name>.js`, registered with `window.CfTurnstileTroll.define('<name>', …)`, and
   `public/troll/<name>.css` if needed.

### JavaScript API

The behaviour receives a `troll` object:

| Member                       | Description                                                                      |
|------------------------------|----------------------------------------------------------------------------------|
| `root`                       | The troll element.                                                               |
| `reducedMotion`              | `true` when the user prefers reduced motion.                                     |
| `text(key)`                  | The `data-<key>` attribute of the `texts` block.                                 |
| `say(text)`                  | Displays and announces a message.                                                |
| `on(element, type, handler)` | Adds an event listener; an error in the handler ends the troll.                  |
| `later(callback, delay)`     | `setTimeout`, cancelled when the troll ends.                                     |
| `check(button, checked)`     | Sets `aria-checked` on a fake checkbox.                                          |
| `tried()`                    | Marks a first attempt. Every click in the troll already does it.                 |
| `done(text)`                 | Ends the troll, after displaying `text` for a moment when given.                 |

Rules for a troll: it must always be completable, with the keyboard too; it must never ask to type,
copy, download or run anything, nor ask for personal data; it must not imitate the branding of a
real CAPTCHA service.

## Cookie and privacy

The `cf_turnstile_troll` cookie only contains `1` and is not strictly necessary for the website to
work. Check whether your cookie policy covers it. With `cooldown: 0`, no cookie is kept, but the
same visitor may then see several trolls in a row.

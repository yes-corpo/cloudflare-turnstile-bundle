<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\FormExtension;
use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Bridge\Twig\Form\TwigRendererEngine;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormRenderer;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\FactoryRuntimeLoader;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;

class FormThemeTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $formLayoutDir = \dirname((new \ReflectionClass(FormExtension::class))->getFileName(), 2) . '/Resources/views/Form';

        $this->twig = new Environment(new FilesystemLoader([$formLayoutDir, __DIR__ . '/../../templates']), ['strict_variables' => true]);
        $this->twig->addExtension(new AssetExtension(new Packages(new Package(new EmptyVersionStrategy()))));
        $this->twig->addExtension(new FormExtension());
        $this->twig->addExtension(new TranslationExtension());

        $engine = new TwigRendererEngine(['form_div_layout.html.twig', 'form_theme.html.twig'], $this->twig);
        $this->twig->addRuntimeLoader(new FactoryRuntimeLoader([
            FormRenderer::class => static fn () => new FormRenderer($engine),
        ]));
    }

    public function testWidgetMarkup(): void
    {
        $html = $this->renderWidget(['attr' => ['class' => 'my-class', 'data-size' => 'compact']]);

        $this->assertStringContainsString('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>', $html);
        $this->assertSame(1, substr_count($html, 'class='));
        $this->assertStringContainsString('class="my-class cf-turnstile"', $html);
        $this->assertStringContainsString('id="captcha"', $html);
        $this->assertStringContainsString('data-sitekey="site-key"', $html);
        $this->assertStringContainsString('data-response-field-name="captcha"', $html);
        $this->assertStringContainsString('data-size="compact"', $html);
        $this->assertDoesNotMatchRegularExpression('/\sname=/', $html);
        $this->assertStringNotContainsString('required', $html);
    }

    public function testScriptCanBeOmitted(): void
    {
        $html = $this->renderWidget(['turnstile_load_script' => false]);

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('cf-turnstile', $html);
    }

    private function renderWidget(array $options): string
    {
        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new TurnstileType('site-key')], []))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();

        $view = $factory->createNamed('captcha', TurnstileType::class, null, $options)->createView();

        return $this->twig->createTemplate('{{ form_widget(form) }}')->render(['form' => $view]);
    }
}

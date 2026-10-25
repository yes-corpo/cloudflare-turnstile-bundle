<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional\App\TestKernel;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;

class TrollTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel(['environment' => 'dev']);
    }

    public function testApplicationTemplateOverridesTheBundleTemplate(): void
    {
        $html = $this->render('reverse_captcha');

        $this->assertStringContainsString('data-cf-troll="reverse_captcha"', $html);
        $this->assertStringContainsString('overridden reverse captcha', $html);
        $this->assertStringNotContainsString('data-cf-troll-human', $html);
    }

    public function testApplicationTrollIsRenderedWithItsTemplateAndScript(): void
    {
        $html = $this->render('project_troll');

        $this->assertStringContainsString('project troll', $html);
        $this->assertSame(['/bundles/cloudflareturnstile/troll/base.js', '/js/project_troll.js'], $this->assets('script', $html));
    }

    public function testEveryRegisteredTrollIsEnabledExceptTheDisabledOnes(): void
    {
        /** @var TrollPicker $picker */
        $picker = static::getContainer()->get('test.troll_picker');

        $this->assertEqualsCanonicalizing(
            ['fleeing_checkbox', 'hope_grid', 'stuck_loader', 'reverse_captcha', 'project_troll'],
            array_keys($picker->getEnabledTrolls()),
        );
    }

    public function testOnlyTheAssetsOfTheDisplayedTrollAreLoaded(): void
    {
        $html = $this->render('reverse_captcha');

        $this->assertSame(['/bundles/cloudflareturnstile/troll/base.css'], $this->assets('stylesheet', $html));
        $this->assertSame(['/bundles/cloudflareturnstile/troll/base.js', '/bundles/cloudflareturnstile/troll/reverse_captcha.js'], $this->assets('script', $html));
    }

    public function testTrollAssetsCanBeReplacedOrRemoved(): void
    {
        $html = $this->render('hope_grid');
        $this->assertSame(['/bundles/cloudflareturnstile/troll/base.css', '/css/hope_grid.css'], $this->assets('stylesheet', $html));

        $html = $this->render('stuck_loader');
        $this->assertSame(['/bundles/cloudflareturnstile/troll/base.js'], $this->assets('script', $html));
    }

    public function testRealWidgetIsUnchangedByTheTroll(): void
    {
        $realWidget = '/<div\s+id="form_captcha".*?><\/div>/s';

        $this->assertMatchesRegularExpression($realWidget, $withTroll = $this->render('hope_grid'));
        $this->assertStringNotContainsString('data-cf-troll', $withoutTroll = $this->render('hope_grid', ['turnstile_troll' => false]));

        preg_match($realWidget, $withTroll, $expected);
        preg_match($realWidget, $withoutTroll, $actual);
        $this->assertSame($expected, $actual);
    }

    public function testValidationIsUnchangedByTheTroll(): void
    {
        $this->assertStringContainsString('data-cf-troll="hope_grid"', $this->render('hope_grid'));

        $form = $this->createForm();
        $form->submit(['captcha' => 'valid-token']);
        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));

        $form = $this->createForm();
        $form->submit(['captcha' => 'wrong-token']);
        $this->assertFalse($form->isValid());
        $this->assertSame('The captcha is invalid.', $form->getErrors()[0]->getMessage());
    }

    private function render(string $troll, array $captchaOptions = []): string
    {
        $requestStack = static::getContainer()->get('request_stack');
        $requestStack->push(Request::create('/', 'GET', [TrollPicker::FORCE_PARAMETER => $troll]));

        try {
            return static::getContainer()->get('test.twig')
                ->createTemplate('{{ form_widget(form.captcha) }}')
                ->render(['form' => $this->createForm($captchaOptions)->createView()]);
        } finally {
            $requestStack->pop();
        }
    }

    /**
     * @return list<string>
     */
    private function assets(string $type, string $html): array
    {
        $pattern = 'script' === $type ? '/<script src="([^"]+)" defer>/' : '/<link rel="stylesheet" href="([^"]+)">/';
        preg_match_all($pattern, $html, $matches);

        return $matches[1];
    }

    private function createForm(array $captchaOptions = []): FormInterface
    {
        /** @var FormFactoryInterface $factory */
        $factory = static::getContainer()->get('test.form.factory');

        return $factory->createNamedBuilder('form')
            ->add('captcha', TurnstileType::class, $captchaOptions)
            ->getForm();
    }
}

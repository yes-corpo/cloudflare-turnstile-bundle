<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional\App\TestKernel;

class BundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testWidgetIsRenderedWithTheBundleFormTheme(): void
    {
        $view = $this->createForm()->createView();

        $html = static::getContainer()->get('test.twig')
            ->createTemplate('{{ form_widget(form.captcha) }}')
            ->render(['form' => $view]);

        $this->assertStringContainsString('class="cf-turnstile"', $html);
        $this->assertStringContainsString('data-sitekey="site-key"', $html);
        $this->assertStringContainsString('data-response-field-name="form[captcha]"', $html);
    }

    public function testValidTokenIsAccepted(): void
    {
        $form = $this->createForm();
        $form->submit(['captcha' => 'valid-token']);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
    }

    public function testInvalidTokenIsRejectedWithATranslatedMessage(): void
    {
        static::getContainer()->get('test.translator')->setLocale('fr');

        $form = $this->createForm();
        $form->submit(['captcha' => 'wrong-token']);

        $this->assertFalse($form->isValid());
        // Inherited from HiddenType: error_bubbling is true, so the error is attached to the parent form
        $this->assertSame('Captcha invalide.', $form->getErrors()[0]->getMessage());
    }

    public function testErrorStaysOnTheFieldWithoutErrorBubbling(): void
    {
        $form = $this->createForm(['error_bubbling' => false]);
        $form->submit(['captcha' => 'wrong-token']);

        $this->assertCount(0, $form->getErrors());
        $this->assertSame('The captcha is invalid.', $form->get('captcha')->getErrors()[0]->getMessage());
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

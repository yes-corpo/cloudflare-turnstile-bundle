<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Form\Type;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Validation;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\Turnstile;

// Not a TypeTestCase: its EventDispatcher mock without expectations triggers notices on PHPUnit 12
class TurnstileTypeTest extends TestCase
{
    private FormFactoryInterface $factory;

    protected function setUp(): void
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new TurnstileType('site-key')], []))
            ->addExtension(new ValidatorExtension(Validation::createValidator()))
            ->getFormFactory();
    }

    public function testTurnstileConstraintIsAddedByDefault(): void
    {
        $form = $this->factory->create(TurnstileType::class);

        $constraints = $form->getConfig()->getOption('constraints');

        $this->assertCount(1, $constraints);
        $this->assertInstanceOf(Turnstile::class, $constraints[0]);
    }

    public function testTurnstileConstraintIsKeptWhenConstraintsAreOverridden(): void
    {
        $form = $this->factory->create(TurnstileType::class, null, [
            'constraints' => [new Length(max: 2048)],
        ]);

        $constraints = $form->getConfig()->getOption('constraints');

        $this->assertCount(2, $constraints);
        $this->assertInstanceOf(Length::class, $constraints[0]);
        $this->assertInstanceOf(Turnstile::class, $constraints[1]);
    }

    public function testCustomTurnstileConstraintIsNotDuplicated(): void
    {
        $turnstile = new Turnstile();
        $turnstile->message = 'Custom message';

        $form = $this->factory->create(TurnstileType::class, null, [
            'constraints' => $turnstile,
        ]);

        $this->assertSame([$turnstile], $form->getConfig()->getOption('constraints'));
    }

    public function testViewExposesWidgetVariables(): void
    {
        $view = $this->factory->create(TurnstileType::class, null, [
            'turnstile_theme' => 'dark',
            'turnstile_load_script' => false,
        ])->createView();

        $this->assertSame('site-key', $view->vars['site_key']);
        $this->assertSame('dark', $view->vars['turnstile_theme']);
        $this->assertFalse($view->vars['turnstile_load_script']);
    }

    public function testInvalidThemeIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->factory->create(TurnstileType::class, null, ['turnstile_theme' => 'blue']);
    }
}

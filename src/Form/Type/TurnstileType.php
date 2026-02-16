<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\Turnstile;

class TurnstileType extends AbstractType
{
    public function __construct(
        private readonly string $siteKey
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['site_key'] = $this->siteKey;
        $view->vars['turnstile_theme'] = $options['turnstile_theme'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'constraints' => [
                new Turnstile(),
            ],
            'turnstile_theme' => 'auto',
            'label' => false,
        ]);

        $resolver->setAllowedValues('turnstile_theme', ['auto', 'light', 'dark']);
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }
}

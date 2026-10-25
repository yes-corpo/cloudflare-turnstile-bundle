<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\Turnstile;

class TurnstileType extends AbstractType
{
    public function __construct(
        private readonly string $siteKey,
        private readonly ?TrollPicker $trollPicker = null
    ) {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['site_key'] = $this->siteKey;
        $view->vars['turnstile_theme'] = $options['turnstile_theme'];
        $view->vars['turnstile_load_script'] = $options['turnstile_load_script'];

        $troll = $this->trollPicker?->pick($form->getRoot()->getName(), $options['turnstile_troll']);
        $view->vars['turnstile_troll'] = $troll ? [
            'name' => $troll::getName(),
            'template' => $troll->getTemplate(),
            'motion' => $troll->usesMotion(),
            'cooldown' => $this->trollPicker->getCooldown(),
            'stylesheets' => $this->trollPicker->getStylesheets($troll),
            'scripts' => $this->trollPicker->getScripts($troll),
        ] : null;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'turnstile_theme' => 'auto',
            'turnstile_load_script' => true,
            'turnstile_troll' => true,
            'label' => false,
        ]);

        $resolver->setAllowedValues('turnstile_theme', ['auto', 'light', 'dark']);
        $resolver->setAllowedTypes('turnstile_load_script', 'bool');
        $resolver->setAllowedTypes('turnstile_troll', 'bool');

        $resolver->addNormalizer('constraints', static function (Options $options, array $constraints): array {
            foreach ($constraints as $constraint) {
                if ($constraint instanceof Turnstile) {
                    return $constraints;
                }
            }

            $constraints[] = new Turnstile();

            return $constraints;
        });
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }
}

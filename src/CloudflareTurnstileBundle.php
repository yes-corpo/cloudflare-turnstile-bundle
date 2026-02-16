<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\TurnstileValidator;

class CloudflareTurnstileBundle extends AbstractBundle
{
    protected string $extensionAlias = 'cloudflare_turnstile';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('site_key')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('secret_key')->isRequired()->cannotBeEmpty()->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        $services->set(TurnstileValidator::class)
            ->arg('$secretKey', $config['secret_key']);

        $services->set(TurnstileType::class)
            ->arg('$siteKey', $config['site_key']);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasExtension('twig')) {
            return;
        }

        $builder->prependExtensionConfig('twig', [
            'paths' => [
                __DIR__ . '/../templates' => 'CloudflareTurnstile',
            ],
        ]);

        $builder->prependExtensionConfig('twig', [
            'form_themes' => ['@CloudflareTurnstile/form_theme.html.twig'],
        ]);
    }
}

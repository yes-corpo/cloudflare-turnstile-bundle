<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use YesCorpo\Bundle\CloudflareTurnstileBundle\DependencyInjection\Compiler\TrollPass;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Form\Type\TurnstileType;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\FleeingCheckboxTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\HesitantCheckboxTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\HopeGridTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\ReverseCaptchaTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\StuckLoaderTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollInterface;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\TurnstileValidator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;

class CloudflareTurnstileBundle extends AbstractBundle
{
    private const BUILTIN_TROLLS = [
        FleeingCheckboxTroll::class,
        HesitantCheckboxTroll::class,
        HopeGridTroll::class,
        StuckLoaderTroll::class,
        ReverseCaptchaTroll::class,
    ];

    protected string $extensionAlias = 'cloudflare_turnstile';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('site_key')->isRequired()->cannotBeEmpty()->end()
            ->scalarNode('secret_key')->isRequired()->cannotBeEmpty()->end()
            ->arrayNode('troll')
                ->info('Humorous fake CAPTCHA shown before the real challenge, purely cosmetic')
                ->addDefaultsIfNotSet()
                ->children()
                    ->booleanNode('enabled')->defaultFalse()->end()
                    ->floatNode('probability')->defaultValue(0.01)->min(0)->max(1)->end()
                    ->arrayNode('trolls')
                        ->info('Names of the enabled trolls, all the registered ones when empty')
                        ->performNoDeepMerging()
                        ->scalarPrototype()->end()
                    ->end()
                    ->arrayNode('disabled_trolls')->scalarPrototype()->end()->end()
                    ->integerNode('cooldown')
                        ->info('Seconds during which a user who saw a troll will not see another one')
                        ->defaultValue(86400)->min(0)
                    ->end()
                    ->arrayNode('excluded_routes')->scalarPrototype()->end()->end()
                    ->arrayNode('excluded_forms')
                        ->info('Names of the root forms that never display a troll')
                        ->scalarPrototype()->end()
                    ->end()
                    ->scalarNode('stylesheet')
                        ->info('Base stylesheet loaded with every troll, false to load none')
                        ->defaultValue('bundles/cloudflareturnstile/troll/base.css')
                    ->end()
                    ->scalarNode('script')
                        ->info('Base script loaded with every troll, false to load none')
                        ->defaultValue('bundles/cloudflareturnstile/troll/base.js')
                    ->end()
                    ->arrayNode('assets')
                        ->info('Replaces the stylesheet or script of a troll, false to load none')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('stylesheet')->defaultNull()->end()
                                ->scalarNode('script')->defaultNull()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->end();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TrollPass());
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->registerForAutoconfiguration(TrollInterface::class)
            ->addTag(TrollPass::TAG);

        $services = $container->services()
            ->defaults()
            ->autowire()
            ->autoconfigure();

        $services->set(TurnstileValidator::class)
            ->arg('$secretKey', $config['secret_key']);

        $services->set(TurnstileType::class)
            ->arg('$siteKey', $config['site_key']);

        foreach (self::BUILTIN_TROLLS as $troll) {
            $services->set($troll);
        }

        // $trolls is filled by TrollPass
        $services->set(TrollPicker::class)
            ->arg('$trolls', [])
            ->arg('$config', $config['troll'])
            ->arg('$environment', param('kernel.environment'));
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$builder->hasExtension('twig')) {
            return;
        }

        $builder->prependExtensionConfig('twig', [
            'form_themes' => ['@CloudflareTurnstile/form_theme.html.twig'],
        ]);
    }
}

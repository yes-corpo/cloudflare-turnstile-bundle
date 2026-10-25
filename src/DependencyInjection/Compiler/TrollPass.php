<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollInterface;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;

final class TrollPass implements CompilerPassInterface
{
    public const TAG = 'cloudflare_turnstile.troll';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(TrollPicker::class)) {
            return;
        }

        $trolls = [];
        foreach (array_keys($container->findTaggedServiceIds(self::TAG)) as $id) {
            $class = $container->getParameterBag()->resolveValue($container->getDefinition($id)->getClass() ?? $id);

            if (!is_subclass_of($class, TrollInterface::class)) {
                throw new InvalidArgumentException(sprintf('The troll service "%s" must implement "%s".', $id, TrollInterface::class));
            }

            $trolls[$class::getName()] = new Reference($id);
        }

        $picker = $container->getDefinition(TrollPicker::class);
        $config = $picker->getArgument('$config');

        foreach (['trolls' => $config['trolls'], 'disabled_trolls' => $config['disabled_trolls'], 'assets' => array_keys($config['assets'])] as $option => $names) {
            if ($unknown = array_diff($names, array_keys($trolls))) {
                throw new InvalidConfigurationException(sprintf(
                    'Unknown troll(s) "%s" in "cloudflare_turnstile.troll.%s". Available trolls: "%s".',
                    implode('", "', $unknown),
                    $option,
                    implode('", "', array_keys($trolls)),
                ));
            }
        }

        $picker->setArgument('$trolls', $trolls);
    }
}

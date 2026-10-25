<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional\App;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use YesCorpo\Bundle\CloudflareTurnstileBundle\CloudflareTurnstileBundle;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TwigBundle(),
            new CloudflareTurnstileBundle(),
        ];
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/cloudflare-turnstile-bundle/' . Kernel::VERSION . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/cloudflare-turnstile-bundle/' . Kernel::VERSION . '/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $framework = [
            'test' => true,
            'secret' => 'test',
            'form' => true,
            'validation' => true,
            // No application translations: the messages must come from the bundle
            'translator' => ['default_path' => __DIR__ . '/translations', 'fallbacks' => ['en']],
            'http_client' => ['mock_response_factory' => TurnstileResponseFactory::class],
        ];

        // Defaults that changed in Symfony 7, set explicitly to avoid deprecations on 6.4
        if (Kernel::MAJOR_VERSION < 7) {
            $framework += [
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
            ];
            $framework['validation'] = ['email_validation_mode' => 'html5'];
        }

        if (Kernel::MAJOR_VERSION === 7) {
            $framework['property_info'] = ['with_constructor_extractor' => true];
        }

        $container->extension('framework', $framework);
        $container->extension('twig', ['strict_variables' => true, 'default_path' => __DIR__ . '/templates']);
        $container->extension('cloudflare_turnstile', [
            'site_key' => 'site-key',
            'secret_key' => 'secret-key',
        ]);

        $services = $container->services();
        $services->set(TurnstileResponseFactory::class);

        // Unused services are removed from the container: expose the ones the tests need
        $services->alias('test.form.factory', 'form.factory')->public();
        $services->alias('test.twig', 'twig')->public();
        $services->alias('test.translator', 'translator')->public();
    }
}

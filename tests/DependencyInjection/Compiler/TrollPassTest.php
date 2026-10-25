<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use YesCorpo\Bundle\CloudflareTurnstileBundle\DependencyInjection\Compiler\TrollPass;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\HopeGridTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\ReverseCaptchaTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;

class TrollPassTest extends TestCase
{
    public function testTrollsAreIndexedByName(): void
    {
        $container = $this->container();

        (new TrollPass())->process($container);

        $this->assertEquals(
            ['hope_grid' => new Reference(HopeGridTroll::class), 'reverse_captcha' => new Reference('app.troll')],
            $container->getDefinition(TrollPicker::class)->getArgument('$trolls'),
        );
    }

    #[DataProvider('provideUnknownTroll')]
    public function testUnknownTrollIsRejected(string $option, array $value): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(sprintf('Unknown troll(s) "unknown" in "cloudflare_turnstile.troll.%s".', $option));

        (new TrollPass())->process($this->container([$option => $value]));
    }

    public static function provideUnknownTroll(): iterable
    {
        yield ['trolls', ['hope_grid', 'unknown']];
        yield ['disabled_trolls', ['unknown']];
        yield ['assets', ['unknown' => ['stylesheet' => null, 'script' => false]]];
    }

    private function container(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(HopeGridTroll::class)->addTag(TrollPass::TAG);
        $container->register('app.troll', ReverseCaptchaTroll::class)->addTag(TrollPass::TAG);
        $container->register(TrollPicker::class)
            ->setArgument('$trolls', [])
            ->setArgument('$config', $config + ['trolls' => [], 'disabled_trolls' => [], 'assets' => []]);

        return $container;
    }
}

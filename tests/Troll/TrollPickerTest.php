<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Troll;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\HesitantCheckboxTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\HopeGridTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\ReverseCaptchaTroll;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollPicker;

class TrollPickerTest extends TestCase
{
    private const CONFIG = [
        'enabled' => true,
        'probability' => 0.5,
        'trolls' => [],
        'disabled_trolls' => [],
        'cooldown' => 60,
        'excluded_routes' => [],
        'excluded_forms' => [],
        'stylesheet' => 'base.css',
        'script' => 'base.js',
        'assets' => [],
    ];

    public function testTrollIsPickedBelowTheProbability(): void
    {
        $this->assertInstanceOf(HopeGridTroll::class, $this->picker(random: [0.49, 0.5])->pick('form'));
    }

    public function testNoTrollFromTheProbability(): void
    {
        $this->assertNull($this->picker(random: [0.5])->pick('form'));
        $this->assertNull($this->picker(['probability' => 0.0], random: [0.0])->pick('form'));
        $this->assertNotNull($this->picker(['probability' => 1.0], random: [0.999, 0.0])->pick('form'));
    }

    public function testNoTrollWhenDisabled(): void
    {
        $this->assertNull($this->picker(['enabled' => false], random: [0.0])->pick('form'));
    }

    public function testNoTrollAfterARecentOne(): void
    {
        $request = new Request(cookies: [TrollPicker::COOKIE => '1']);

        $this->assertNull($this->picker(random: [0.0], request: $request)->pick('form'));
    }

    public function testNoTrollWhenExcluded(): void
    {
        $request = new Request(attributes: ['_route' => 'payment']);

        $this->assertNull($this->picker(random: [0.0])->pick('form', false));
        $this->assertNull($this->picker(['excluded_forms' => ['form']], random: [0.0])->pick('form'));
        $this->assertNull($this->picker(['excluded_routes' => ['payment']], random: [0.0], request: $request)->pick('form'));
    }

    public function testASingleTrollPerRequest(): void
    {
        $picker = $this->picker(random: [0.0, 0.0, 0.0, 0.0]);

        $this->assertNotNull($picker->pick('first'));
        $this->assertNull($picker->pick('second'));
    }

    public function testTrollIsForcedInDevOnly(): void
    {
        $request = new Request([TrollPicker::FORCE_PARAMETER => 'reverse_captcha']);
        $config = ['enabled' => false, 'disabled_trolls' => ['reverse_captcha']];

        $this->assertInstanceOf(ReverseCaptchaTroll::class, $this->picker($config, request: $request, environment: 'dev')->pick('form'));
        $this->assertNull($this->picker($config, request: $request, environment: 'prod')->pick('form'));
        $this->assertNull($this->picker($config, request: $request, environment: 'test')->pick('form'));
    }

    public function testForcingStillHonoursExclusions(): void
    {
        $request = new Request([TrollPicker::FORCE_PARAMETER => 'random']);

        $this->assertNotNull($this->picker(random: [0.0], request: $request, environment: 'dev')->pick('form'));
        $this->assertNull($this->picker(['excluded_forms' => ['form']], request: $request, environment: 'dev')->pick('form'));
    }

    public function testEnabledTrolls(): void
    {
        $this->assertSame(['hesitant_checkbox', 'hope_grid', 'reverse_captcha'], array_keys($this->picker()->getEnabledTrolls()));
        $this->assertSame(['hope_grid'], array_keys($this->picker(['trolls' => ['hope_grid', 'reverse_captcha'], 'disabled_trolls' => ['reverse_captcha']])->getEnabledTrolls()));
        $this->assertSame(['hesitant_checkbox', 'reverse_captcha'], array_keys($this->picker(['disabled_trolls' => ['hope_grid']])->getEnabledTrolls()));
    }

    public function testOnlyEnabledTrollsAreDrawn(): void
    {
        $picker = $this->picker(['disabled_trolls' => ['hesitant_checkbox', 'hope_grid']], random: [0.0, 0.0]);

        $this->assertInstanceOf(ReverseCaptchaTroll::class, $picker->pick('form'));
    }

    public function testAssets(): void
    {
        $picker = $this->picker([
            'assets' => [
                'hope_grid' => ['stylesheet' => 'grid.css', 'script' => false],
            ],
        ]);

        $this->assertSame(['base.css', 'grid.css'], $picker->getStylesheets(new HopeGridTroll()));
        $this->assertSame(['base.js'], $picker->getScripts(new HopeGridTroll()));
        $this->assertSame(['base.css'], $picker->getStylesheets(new ReverseCaptchaTroll()));
        $this->assertSame(['base.js', 'bundles/cloudflareturnstile/troll/reverse_captcha.js'], $picker->getScripts(new ReverseCaptchaTroll()));
    }

    public function testBaseAssetsCanBeRemoved(): void
    {
        $picker = $this->picker(['stylesheet' => false, 'script' => false]);

        $this->assertSame([], $picker->getStylesheets(new ReverseCaptchaTroll()));
        $this->assertSame(['bundles/cloudflareturnstile/troll/reverse_captcha.js'], $picker->getScripts(new ReverseCaptchaTroll()));
    }

    public function testTheDefaultRateMatchesTheProbability(): void
    {
        mt_srand(42);
        $picked = 0;

        for ($i = 0; $i < 10000; ++$i) {
            $picker = new TrollPicker($this->requestStack(new Request()), ['hope_grid' => new HopeGridTroll()], ['probability' => 0.01] + self::CONFIG, 'prod');
            $picked += null !== $picker->pick('form') ? 1 : 0;
        }

        $this->assertGreaterThan(50, $picked);
        $this->assertLessThan(150, $picked);
    }

    /**
     * @param list<float> $random
     */
    private function picker(array $config = [], array $random = [], ?Request $request = null, string $environment = 'prod'): TrollPicker
    {
        $trolls = [
            'hesitant_checkbox' => new HesitantCheckboxTroll(),
            'hope_grid' => new HopeGridTroll(),
            'reverse_captcha' => new ReverseCaptchaTroll(),
        ];

        return new TrollPicker(
            $this->requestStack($request ?? new Request()),
            $trolls,
            $config + self::CONFIG,
            $environment,
            static function () use (&$random): float {
                return array_shift($random) ?? throw new \LogicException('No more random value.');
            },
        );
    }

    private function requestStack(Request $request): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }
}

<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class TrollPicker
{
    public const COOKIE = 'cf_turnstile_troll';

    public const FORCE_PARAMETER = '_turnstile_troll';

    private const PICKED_ATTRIBUTE = '_cf_turnstile_troll_picked';

    private readonly \Closure $random;

    /**
     * @param array<string, TrollInterface> $trolls Every registered troll, indexed by name
     * @param array{enabled: bool, probability: float, trolls: list<string>, disabled_trolls: list<string>, cooldown: int, excluded_routes: list<string>, excluded_forms: list<string>, stylesheet: string|false|null, script: string|false|null, assets: array<string, array{stylesheet: string|false|null, script: string|false|null}>} $config
     * @param (\Closure(): float)|null $random Returns a float in [0, 1)
     */
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly array $trolls,
        private readonly array $config,
        private readonly string $environment,
        ?\Closure $random = null,
    ) {
        $this->random = $random ?? static fn (): float => mt_rand(0, mt_getrandmax() - 1) / mt_getrandmax();
    }

    public function pick(string $formName, bool $allowed = true): ?TrollInterface
    {
        $request = $this->requestStack->getMainRequest();

        if (null === $request || !$allowed || $this->isExcluded($request, $formName)) {
            return null;
        }

        if ($request->attributes->has(self::PICKED_ATTRIBUTE)) {
            return null;
        }

        $troll = $this->forced($request) ?? $this->draw($request);

        if (null !== $troll) {
            $request->attributes->set(self::PICKED_ATTRIBUTE, $troll::getName());
        }

        return $troll;
    }

    public function getCooldown(): int
    {
        return $this->config['cooldown'];
    }

    /**
     * @return array<string, TrollInterface>
     */
    public function getEnabledTrolls(): array
    {
        $trolls = [] === $this->config['trolls'] ? $this->trolls : array_intersect_key($this->trolls, array_flip($this->config['trolls']));

        return array_diff_key($trolls, array_flip($this->config['disabled_trolls']));
    }

    /**
     * @return list<string>
     */
    public function getStylesheets(TrollInterface $troll): array
    {
        return $this->assets($troll, 'stylesheet', $troll->getStylesheet());
    }

    /**
     * @return list<string>
     */
    public function getScripts(TrollInterface $troll): array
    {
        return $this->assets($troll, 'script', $troll->getScript());
    }

    private function isExcluded(Request $request, string $formName): bool
    {
        return \in_array($formName, $this->config['excluded_forms'], true)
            || \in_array($request->attributes->get('_route'), $this->config['excluded_routes'], true);
    }

    private function forced(Request $request): ?TrollInterface
    {
        if ('dev' !== $this->environment || !$request->query->has(self::FORCE_PARAMETER)) {
            return null;
        }

        $name = $request->query->get(self::FORCE_PARAMETER);

        if ('random' === $name) {
            return $this->pickAmong($this->getEnabledTrolls() ?: $this->trolls);
        }

        return $this->trolls[$name] ?? null;
    }

    private function draw(Request $request): ?TrollInterface
    {
        if (!$this->config['enabled'] || $request->cookies->has(self::COOKIE)) {
            return null;
        }

        if (($this->random)() >= $this->config['probability']) {
            return null;
        }

        return $this->pickAmong($this->getEnabledTrolls());
    }

    /**
     * @return list<string>
     */
    private function assets(TrollInterface $troll, string $type, ?string $default): array
    {
        $assets = [$this->config[$type], $this->config['assets'][$troll::getName()][$type] ?? $default];

        return array_values(array_filter($assets, static fn (mixed $asset): bool => \is_string($asset) && '' !== $asset));
    }

    /**
     * @param array<string, TrollInterface> $trolls
     */
    private function pickAmong(array $trolls): ?TrollInterface
    {
        if ([] === $trolls) {
            return null;
        }

        $trolls = array_values($trolls);
        $index = min((int) floor(($this->random)() * \count($trolls)), \count($trolls) - 1);

        return $trolls[$index];
    }
}

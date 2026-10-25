<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

/**
 * @internal
 */
abstract class AbstractTroll implements TrollInterface
{
    public function getTemplate(): string
    {
        return sprintf('@CloudflareTurnstile/troll/%s.html.twig', static::getName());
    }

    public function getStylesheet(): ?string
    {
        return null;
    }

    public function getScript(): ?string
    {
        return static::asset('js');
    }

    public function usesMotion(): bool
    {
        return false;
    }

    protected static function asset(string $extension): string
    {
        return sprintf('bundles/cloudflareturnstile/troll/%s.%s', static::getName(), $extension);
    }
}

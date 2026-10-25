<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

final class FleeingCheckboxTroll extends AbstractTroll
{
    public static function getName(): string
    {
        return 'fleeing_checkbox';
    }

    public function getStylesheet(): string
    {
        return self::asset('css');
    }

    public function usesMotion(): bool
    {
        return true;
    }
}

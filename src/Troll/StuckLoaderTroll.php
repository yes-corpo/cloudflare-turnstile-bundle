<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

final class StuckLoaderTroll extends AbstractTroll
{
    public static function getName(): string
    {
        return 'stuck_loader';
    }

    public function getStylesheet(): string
    {
        return self::asset('css');
    }
}

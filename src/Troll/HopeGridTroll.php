<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

final class HopeGridTroll extends AbstractTroll
{
    public static function getName(): string
    {
        return 'hope_grid';
    }

    public function getStylesheet(): string
    {
        return self::asset('css');
    }
}

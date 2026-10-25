<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

final class ReverseCaptchaTroll extends AbstractTroll
{
    public static function getName(): string
    {
        return 'reverse_captcha';
    }
}

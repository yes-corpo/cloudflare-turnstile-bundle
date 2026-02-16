<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Turnstile extends Constraint
{
    public string $message = 'Captcha invalide.';

    public function validatedBy(): string
    {
        return TurnstileValidator::class;
    }
}

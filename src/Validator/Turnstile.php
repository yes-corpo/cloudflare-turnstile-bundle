<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Turnstile extends Constraint
{
    public string $message = 'The captcha is invalid.';
    public string $technicalErrorMessage = 'The captcha could not be verified, please try again.';

    public function validatedBy(): string
    {
        return TurnstileValidator::class;
    }
}

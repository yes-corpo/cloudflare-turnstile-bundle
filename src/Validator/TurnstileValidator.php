<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Validator;

use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TurnstileValidator extends ConstraintValidator
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $secretKey
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof Turnstile) {
            throw new UnexpectedTypeException($constraint, Turnstile::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        try {
            $response = $this->client->request(Request::METHOD_POST, self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $value,
                    // Optionnel : 'remoteip' => $request->getClientIp()
                ],
            ]);

            $content = $response->toArray();

            if (!($content['success'] ?? false)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
        } catch (Exception) {
            $this->context->buildViolation('Technical error during captcha verification.')
                ->addViolation();
        }
    }
}

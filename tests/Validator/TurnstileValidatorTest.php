<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Validator;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\Turnstile;
use YesCorpo\Bundle\CloudflareTurnstileBundle\Validator\TurnstileValidator;

/**
 * @extends ConstraintValidatorTestCase<TurnstileValidator>
 */
class TurnstileValidatorTest extends ConstraintValidatorTestCase
{
    private MockHttpClient $client;
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        $this->client = new MockHttpClient();
        $this->requestStack = new RequestStack();

        parent::setUp();
    }

    protected function createValidator(): TurnstileValidator
    {
        return new TurnstileValidator($this->client, $this->requestStack, 'secret-key');
    }

    public function testValidTokenRaisesNoViolation(): void
    {
        $this->client->setResponseFactory(new MockResponse('{"success": true}'));

        $this->validator->validate('token', new Turnstile());

        $this->assertNoViolation();
    }

    public function testInvalidTokenRaisesViolation(): void
    {
        $this->client->setResponseFactory(new MockResponse('{"success": false, "error-codes": ["invalid-input-response"]}'));

        $constraint = new Turnstile();
        $this->validator->validate('token', $constraint);

        $this->buildViolation($constraint->message)->assertRaised();
    }

    public function testTransportErrorRaisesTechnicalViolation(): void
    {
        $this->client->setResponseFactory(static fn () => throw new TransportException('Network down'));

        $constraint = new Turnstile();
        $this->validator->validate('token', $constraint);

        $this->buildViolation($constraint->technicalErrorMessage)->assertRaised();
    }

    public function testRequestSendsSecretTokenAndClientIp(): void
    {
        $this->requestStack->push(Request::create('/', server: ['REMOTE_ADDR' => '203.0.113.7']));

        $response = new MockResponse('{"success": true}');
        $this->client->setResponseFactory($response);

        $this->validator->validate('token', new Turnstile());

        $this->assertSame('POST', $response->getRequestMethod());
        $this->assertSame('https://challenges.cloudflare.com/turnstile/v0/siteverify', $response->getRequestUrl());
        $this->assertSame(
            'secret=secret-key&response=token&remoteip=203.0.113.7',
            $response->getRequestOptions()['body']
        );
    }

    public function testUnexpectedConstraintIsRejected(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('token', new NotBlank());
    }
}

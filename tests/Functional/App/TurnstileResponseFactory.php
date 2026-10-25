<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional\App;

use Symfony\Component\HttpClient\Response\MockResponse;

class TurnstileResponseFactory
{
    public function __invoke(string $method, string $url, array $options): MockResponse
    {
        parse_str($options['body'], $body);

        $success = 'secret-key' === ($body['secret'] ?? null) && 'valid-token' === ($body['response'] ?? null);

        return new MockResponse(json_encode(['success' => $success]));
    }
}

<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Troll;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The fake controls must never change the submitted data, nor ask the user to copy, download or run anything.
 */
class TrollMarkupTest extends TestCase
{
    #[DataProvider('provideTemplate')]
    public function testTemplateHasNoSubmittableControl(string $template): void
    {
        $markup = file_get_contents($template);

        $this->assertDoesNotMatchRegularExpression('/\sname=|<input|<textarea|<select|<form|<a\s/i', $markup);

        preg_match_all('/<button\b[^>]*>/', $markup, $buttons);
        foreach ($buttons[0] as $button) {
            $this->assertStringContainsString('type="button"', $button);
        }
    }

    #[DataProvider('provideScript')]
    public function testScriptHasNoDangerousApi(string $script): void
    {
        $this->assertDoesNotMatchRegularExpression('/clipboard|execCommand|download|\.submit\(|innerHTML|eval\(/i', file_get_contents($script));
    }

    public static function provideTemplate(): iterable
    {
        foreach (glob(__DIR__ . '/../../templates/troll/*.html.twig') as $template) {
            yield basename($template) => [$template];
        }
    }

    public static function provideScript(): iterable
    {
        foreach (glob(__DIR__ . '/../../public/troll/*.js') as $script) {
            yield basename($script) => [$script];
        }
    }
}

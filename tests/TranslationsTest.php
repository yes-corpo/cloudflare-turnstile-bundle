<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Loader\XliffFileLoader;

class TranslationsTest extends TestCase
{
    private const DIR = __DIR__ . '/../translations';

    #[DataProvider('provideTranslationFile')]
    public function testEveryLocaleHasTheEnglishMessages(string $domain, string $locale): void
    {
        $loader = new XliffFileLoader();
        $english = $loader->load(sprintf('%s/%s.en.xlf', self::DIR, $domain), 'en', $domain)->all($domain);
        $messages = $loader->load(sprintf('%s/%s.%s.xlf', self::DIR, $domain, $locale), $locale, $domain)->all($domain);

        $this->assertSame(array_keys($english), array_keys($messages));
        $this->assertNotContains('', $messages);
    }

    public static function provideTranslationFile(): iterable
    {
        foreach (glob(self::DIR . '/*.xlf') as $file) {
            [$domain, $locale] = explode('.', basename($file, '.xlf'));

            yield basename($file) => [$domain, $locale];
        }
    }
}

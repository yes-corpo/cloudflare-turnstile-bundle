<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Troll;

interface TrollInterface
{
    public static function getName(): string;

    public function getTemplate(): string;

    /**
     * Asset path, loaded after the base stylesheet.
     */
    public function getStylesheet(): ?string;

    /**
     * Asset path, loaded after the base script.
     */
    public function getScript(): ?string;

    public function usesMotion(): bool;
}

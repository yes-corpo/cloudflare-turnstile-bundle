<?php

namespace YesCorpo\Bundle\CloudflareTurnstileBundle\Tests\Functional\App;

use YesCorpo\Bundle\CloudflareTurnstileBundle\Troll\TrollInterface;

class ProjectTroll implements TrollInterface
{
    public static function getName(): string
    {
        return 'project_troll';
    }

    public function getTemplate(): string
    {
        return 'troll/project_troll.html.twig';
    }

    public function getStylesheet(): ?string
    {
        return null;
    }

    public function getScript(): string
    {
        return 'js/project_troll.js';
    }

    public function usesMotion(): bool
    {
        return false;
    }
}

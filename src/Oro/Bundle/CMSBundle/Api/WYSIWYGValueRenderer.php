<?php

namespace Oro\Bundle\CMSBundle\Api;

use Twig\Environment;

/**
 * Provides functionality to build a rendered version of a WYSIWYG value.
 */
class WYSIWYGValueRenderer
{
    private const TWIG_TEMPLATE = '@OroCMS/Api/Field/render_content.html.twig';

    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(?string $value, ?string $style): ?string
    {
        $result = null;
        if ($value || $style) {
            $result = $this->twig->render(self::TWIG_TEMPLATE, ['value' => (string)$value, 'style' => (string)$style]);
        }

        return $result;
    }
}

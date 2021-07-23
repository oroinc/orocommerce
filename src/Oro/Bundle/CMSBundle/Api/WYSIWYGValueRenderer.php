<?php

namespace Oro\Bundle\CMSBundle\Api;

use Twig\Environment;

/**
 * Provides functionality to build a rendered version of a WYSIWYG value.
 */
class WYSIWYGValueRenderer
{
    private const TWIG_TEMPLATE      = 'OroApiBundle:Field:render_content.html.twig';
    private const CSS_STYLE_TEMPLATE = '<style type="text/css">%s</style>';

    /** @var Environment */
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(?string $value, ?string $style): ?string
    {
        if ($value) {
            $value = $this->renderTwigValue($value);
        }
        if ($style) {
            $style = $this->renderTwigValue($style);
        }

        $result = null;
        if ($style) {
            $result = sprintf(self::CSS_STYLE_TEMPLATE, $style);
        }
        if ($value) {
            $result .= $value;
        }

        return $result;
    }

    private function renderTwigValue(string $value): string
    {
        return $this->twig->render(self::TWIG_TEMPLATE, ['value' => $value]);
    }
}

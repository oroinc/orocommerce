<?php

namespace Oro\Bundle\CMSBundle\Api;

use Twig\Environment;

/**
 * Provides functionality to build a rendered version of a WYSIWYG value.
 */
class WYSIWYGValueRenderer
{
    private const TWIG_TEMPLATE      = '@OroApi/Field/render_content.html.twig';
    private const CSS_STYLE_TEMPLATE = '<style type="text/css">%s</style>';

    /** @var Environment */
    private $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string|null $value
     * @param string|null $style
     *
     * @return string|null
     */
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

    /**
     * @param string $value
     *
     * @return string
     */
    private function renderTwigValue(string $value): string
    {
        return $this->twig->render(self::TWIG_TEMPLATE, ['value' => $value]);
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig function to render an inline template with "@oro_cms.twig.renderer":
 *   - render_content
 *
 * Allowed variables, tags and functions are limited by "@oro_cms.twig.content_security_policy" service.
 */
class TwigInVariablesExtension extends AbstractExtension
{
    /** @var Environment */
    protected $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('render_content', [$this, 'renderContent'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $content
     * @return string
     */
    public function renderContent($content)
    {
        $template = $this->twig->createTemplate($content);

        return $template->render([]);
    }
}

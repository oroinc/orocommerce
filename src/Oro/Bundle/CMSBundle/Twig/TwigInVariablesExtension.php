<?php

namespace Oro\Bundle\CMSBundle\Twig;

/**
 * This extension renders twig at variables passed to template with "@oro_cms.twig.renderer".
 * Allowed in variables tags and functions are limited by "@oro_cms.twig.content_security_policy" service,
 * to add something to whitelist use that public methods.
 */
class TwigInVariablesExtension extends \Twig_Extension
{
    /** @var \Twig_Environment */
    protected $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('render_content', [$this, 'renderContent'], ['is_safe' => ['html']]),
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

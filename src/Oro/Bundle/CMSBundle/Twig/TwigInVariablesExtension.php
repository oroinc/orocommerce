<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a Twig function to render an inline template with "@oro_cms.twig.renderer":
 *   - render_content
 *
 * Allowed variables, tags and functions are limited by "@oro_cms.twig.content_security_policy" service.
 */
class TwigInVariablesExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        $template = $this->container->get('oro_cms.twig.renderer')
            ->createTemplate($content);

        return $template->render([]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_cms.twig.renderer' => Environment::class,
        ];
    }
}

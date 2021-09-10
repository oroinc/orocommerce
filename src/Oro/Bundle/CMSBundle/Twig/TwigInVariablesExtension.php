<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
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
    private ContainerInterface $container;
    private LoggerInterface $logger;

    public function __construct(ContainerInterface $container, LoggerInterface $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
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
        if (!$content) {
            return '';
        }

        try {
            return $this->getCmsTwigRenderer()->createTemplate($content)->render([]);
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf('Could not render content: %s', $content),
                ['exception' => $exception]
            );
        }

        return '';
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

    private function getCmsTwigRenderer(): Environment
    {
        return $this->container->get('oro_cms.twig.renderer');
    }
}

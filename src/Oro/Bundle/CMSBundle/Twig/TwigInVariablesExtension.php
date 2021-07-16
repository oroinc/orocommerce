<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
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
class TwigInVariablesExtension extends AbstractExtension implements ServiceSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = new NullLogger();
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
        $renderedContent = '';
        if ($content) {
            try {
                $template = $this->container->get('oro_cms.twig.renderer')
                    ->createTemplate($content);

                $renderedContent = $template->render([]);
            } catch (\Throwable $exception) {
                $this->logger->error(sprintf('Could not render content: %s', $content), ['e' => $exception]);
            }
        }

        return $renderedContent;
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

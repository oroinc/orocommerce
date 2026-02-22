<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetRenderer;
use Oro\Bundle\CMSBundle\ContentWidget\WysiwygWidgetIconRenderer;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to render a content widget:
 *   - widget
 *   - widget_icon
 */
class WidgetExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('widget', [$this, 'renderContentWidget'], ['is_safe' => ['html']]),
            new TwigFunction('widget_icon', [$this, 'renderWysiwygWidgetIcon'], ['is_safe' => ['html']]),
        ];
    }

    public function renderContentWidget(string $widgetName): string
    {
        return $this->getContentWidgetRenderer()->render($widgetName);
    }

    public function renderWysiwygWidgetIcon(string $widgetName, array $options = []): string
    {
        return $this->getWysiwygWidgetIconRenderer()->render($widgetName, $options);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ContentWidgetRenderer::class,
            WysiwygWidgetIconRenderer::class
        ];
    }

    private function getContentWidgetRenderer(): ContentWidgetRenderer
    {
        return $this->container->get(ContentWidgetRenderer::class);
    }

    private function getWysiwygWidgetIconRenderer(): WysiwygWidgetIconRenderer
    {
        return $this->container->get(WysiwygWidgetIconRenderer::class);
    }
}

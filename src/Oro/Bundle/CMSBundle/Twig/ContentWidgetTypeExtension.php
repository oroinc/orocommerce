<?php

namespace Oro\Bundle\CMSBundle\Twig;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Provider\ContentWidgetLayoutProvider;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides Twig filters to render a content widget type labels:
 *   - content_widget_type_label
 *   - content_widget_layout_label
 */
class ContentWidgetTypeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('content_widget_type_label', [$this, 'getContentWidgetTypeLabel']),
            new TwigFilter('content_widget_layout_label', [$this, 'getContentWidgetLayoutLabel']),
        ];
    }

    public function getContentWidgetTypeLabel(string $widgetType): string
    {
        if (!$widgetType) {
            return $widgetType;
        }

        $contentWidgetType = $this->getContentWidgetTypeRegistry()->getWidgetType($widgetType);

        return $contentWidgetType
            ? $this->getTranslator()->trans($contentWidgetType->getLabel())
            : $widgetType;
    }

    public function getContentWidgetLayoutLabel(string $layout, string $widgetType): string
    {
        $label = $this->getContentWidgetLayoutProvider()->getWidgetLayoutLabel($widgetType, $layout);

        return $this->getTranslator()->trans($label);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            TranslatorInterface::class,
            ContentWidgetTypeRegistry::class,
            ContentWidgetLayoutProvider::class,
        ];
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->container->get(TranslatorInterface::class);
    }

    private function getContentWidgetTypeRegistry(): ContentWidgetTypeRegistry
    {
        return $this->container->get(ContentWidgetTypeRegistry::class);
    }

    private function getContentWidgetLayoutProvider(): ContentWidgetLayoutProvider
    {
        return $this->container->get(ContentWidgetLayoutProvider::class);
    }
}

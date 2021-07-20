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
 * Provides a Twig filter to render a content widget type label:
 *   - content_widget_type_label
 */
class ContentWidgetTypeExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

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

        $contentWidgetType = $this->container->get(ContentWidgetTypeRegistry::class)
            ->getWidgetType($widgetType);

        return $contentWidgetType
            ? $this->container->get(TranslatorInterface::class)->trans($contentWidgetType->getLabel())
            : $widgetType;
    }

    public function getContentWidgetLayoutLabel(string $layout, string $widgetType): string
    {
        $label = $this->container->get(ContentWidgetLayoutProvider::class)->getWidgetLayoutLabel($widgetType, $layout);

        return $this->container->get(TranslatorInterface::class)->trans($label);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            TranslatorInterface::class,
            ContentWidgetTypeRegistry::class,
            ContentWidgetLayoutProvider::class,
        ];
    }
}

<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeInterface;
use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Twig\Environment;

/**
 * Layout data provider for Content Widgets.
 * Adds possibility to get appropriate data and template according given Content Widget entity.
 */
class ContentWidgetDataProvider implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var Environment */
    private $twig;

    public function __construct(ContentWidgetTypeRegistry $contentWidgetTypeRegistry, Environment $twig)
    {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->twig = $twig;
    }

    public function getWidgetData(ContentWidget $contentWidget): array
    {
        $contentWidgetType = $this->getType($contentWidget);

        return $contentWidgetType ? $contentWidgetType->getWidgetData($contentWidget) : [];
    }

    public function getDefaultTemplate(ContentWidget $contentWidget): string
    {
        $contentWidgetType = $this->getType($contentWidget);

        return $contentWidgetType ? $contentWidgetType->getDefaultTemplate($contentWidget, $this->twig) : '';
    }

    private function getType(ContentWidget $contentWidget): ?ContentWidgetTypeInterface
    {
        $contentWidgetType = $this->contentWidgetTypeRegistry->getWidgetType($contentWidget->getWidgetType());
        if (!$contentWidgetType && $this->logger) {
            $this->logger->error(
                sprintf('Content widget type %s is not registered', $contentWidget->getWidgetType())
            );
        }

        return $contentWidgetType;
    }
}

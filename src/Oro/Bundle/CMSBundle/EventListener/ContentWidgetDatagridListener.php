<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\DataGridBundle\Event\PreBuild;

/**
 * Changes datagrid query to get only inline/block content widgets.
 */
class ContentWidgetDatagridListener
{
    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /** @var bool */
    private $isInline;

    public function __construct(ContentWidgetTypeRegistry $contentWidgetTypeRegistry, bool $isInline)
    {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
        $this->isInline = $isInline;
    }

    public function onPreBuild(PreBuild $event): void
    {
        $types = [];
        foreach ($this->contentWidgetTypeRegistry->getTypes() as $contentWidgetType) {
            if ($this->isInline === $contentWidgetType->isInline()) {
                $types[] = $contentWidgetType::getName();
            }
        }

        $event->getParameters()->set('contentWidgetTypes', $types);
    }
}

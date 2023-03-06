<?php

declare(strict_types=1);

namespace Oro\Bundle\CMSBundle\DataCollector;

use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\LayoutBundle\DataCollector\DataCollectorLayoutNameProviderInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Provides the layout name for data collector taking into account content_widget context var.
 */
class DataCollectorContentWidgetLayoutNameProvider implements DataCollectorLayoutNameProviderInterface
{
    public function getNameByContext(ContextInterface $context): string
    {
        $contentWidget = $context->getOr('content_widget');
        if ($contentWidget instanceof ContentWidget && $contentWidget->getWidgetType()) {
            return 'Content Widget: ' . $contentWidget->getWidgetType();
        }

        return '';
    }
}

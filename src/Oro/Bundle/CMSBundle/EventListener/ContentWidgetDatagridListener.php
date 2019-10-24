<?php

namespace Oro\Bundle\CMSBundle\EventListener;

use Oro\Bundle\CMSBundle\ContentWidget\ContentWidgetTypeRegistry;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;

/**
 * Adds inline property to the content widgets datagrid.
 */
class ContentWidgetDatagridListener
{
    /** @var string */
    private const COLUMN_INLINE = 'inline';

    /** @var ContentWidgetTypeRegistry */
    private $contentWidgetTypeRegistry;

    /**
     * @param ContentWidgetTypeRegistry $contentWidgetTypeRegistry
     */
    public function __construct(ContentWidgetTypeRegistry $contentWidgetTypeRegistry)
    {
        $this->contentWidgetTypeRegistry = $contentWidgetTypeRegistry;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();
        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMN_INLINE => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                ],
            ]
        );
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $widgetType = $this->contentWidgetTypeRegistry->getWidgetType($record->getValue('widgetType'));

            $record->addData([self::COLUMN_INLINE => $widgetType ? $widgetType->isInline() : false]);
        }
    }
}

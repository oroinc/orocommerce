<?php

namespace Oro\Bundle\InventoryBundle\EventListener\Frontend;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Adds information required to highlight upcoming products to storefront product grid.
 */
class ProductDatagridUpcomingLabelListener
{
    private const SELECT_PATH = '[source][query][select]';

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(self::SELECT_PATH, ['integer.is_upcoming as is_upcoming']);
        $config->offsetAddToArrayByPath(self::SELECT_PATH, ['datetime.availability_date as availability_date']);

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                'is_upcoming' => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_BOOLEAN,
                ],
                'availability_date' => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_DATETIME,
                ],
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $availabilityDate = $record->getValue('availability_date');
            if ('' === $availabilityDate) {
                $record->setValue('availability_date', null);
            }
        }
    }
}

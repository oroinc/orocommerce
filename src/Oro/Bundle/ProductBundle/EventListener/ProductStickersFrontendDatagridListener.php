<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

/**
 * Adds information about product stickers to storefront product grid.
 * @see \Oro\Bundle\ProductBundle\Layout\DataProvider\ProductStickersProvider
 */
class ProductStickersFrontendDatagridListener
{
    private const COLUMNS_PRODUCT_STICKERS = 'stickers';

    public function onPreBuild(PreBuild $event): void
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMNS_PRODUCT_STICKERS => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                ]
            ]
        );
    }

    public function onResultAfter(SearchResultAfter $event): void
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();
        foreach ($records as $record) {
            $stickers = [];
            if ($record->getValue('newArrival')) {
                $stickers[] = ['type' => 'new_arrival'];
            }
            $record->addData([self::COLUMNS_PRODUCT_STICKERS => $stickers]);
        }
    }
}

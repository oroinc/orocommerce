<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

class ProductStickersFrontendDatagridListener
{
    /**
     * @internal
     */
    const COLUMNS_PRODUCT_STICKERS = 'stickers';

    /**
     * @internal
     */
    const STICKER_TYPE_FIELD_NAME = 'type';

    /**
     * @internal
     */
    const NEW_ARRIVAL_STICKER_TYPE = 'new_arrival';

    /**
     * @internal
     */
    const GRID_NEW_ARRIVALS_FIELD_NAME = 'newArrival';

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[properties]',
            [
                self::COLUMNS_PRODUCT_STICKERS => [
                    'type' => 'field',
                    'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY,
                ],
            ]
        );
    }

    /**
     * @param SearchResultAfter $event
     */
    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $this->fillStickersField($record);
        }
    }

    /**
     * @param ResultRecord $record
     */
    private function fillStickersField(ResultRecord $record)
    {
        $stickers = [];

        $this->addNewArrivalSticker($stickers, $record);

        $record->addData([self::COLUMNS_PRODUCT_STICKERS => $stickers]);
    }

    /**
     * @param array        $stickers
     * @param ResultRecord $record
     */
    private function addNewArrivalSticker(array &$stickers, ResultRecord $record)
    {
        if ($record->getValue(self::GRID_NEW_ARRIVALS_FIELD_NAME)) {
            $this->addSticker($stickers, self::NEW_ARRIVAL_STICKER_TYPE);
        }
    }

    /**
     * @param array  $stickers
     * @param string $type
     */
    private function addSticker(array &$stickers, $type)
    {
        $stickers[] = [
            self::STICKER_TYPE_FIELD_NAME => $type,
        ];
    }
}

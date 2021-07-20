<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\ProductBundle\Provider\ProductNewArrivalStickerTrait;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

class ProductStickersFrontendDatagridListener
{
    use ProductNewArrivalStickerTrait;

    /**
     * @internal
     */
    const COLUMNS_PRODUCT_STICKERS = 'stickers';

    /**
     * @internal
     */
    const GRID_NEW_ARRIVALS_FIELD_NAME = 'newArrival';

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

    public function onResultAfter(SearchResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        foreach ($records as $record) {
            $this->fillStickersField($record);
        }
    }

    private function fillStickersField(ResultRecord $record)
    {
        $stickers = [];

        $this->addNewArrivalSticker($stickers, $record);

        $record->addData([self::COLUMNS_PRODUCT_STICKERS => $stickers]);
    }

    private function addNewArrivalSticker(array &$stickers, ResultRecord $record)
    {
        if ($record->getValue(self::GRID_NEW_ARRIVALS_FIELD_NAME)) {
            $this->addSticker($stickers, $this->getNewArrivalSticker());
        }
    }

    private function addSticker(array &$stickers, array $sticker)
    {
        $stickers[] = $sticker;
    }
}

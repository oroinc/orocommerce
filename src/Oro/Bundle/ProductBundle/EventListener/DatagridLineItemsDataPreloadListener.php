<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\DatagridLineItemsDataEvent;

/**
 * Preloads line items.
 */
class DatagridLineItemsDataPreloadListener
{
    /** @var PreloadingManager */
    private $preloadingManager;

    /** @var array */
    private const FIELDS_FOR_SIMPLE = [
        'product' => [
            'category' => [
                'highlightLowInventory' => [],
                'isUpcoming' => [],
                'maximumQuantityToOrder' => [],
                'minimumQuantityToOrder' => [],
            ],
            'highlightLowInventory' => [],
            'images' => [
                'image' => [
                    'digitalAsset' => [
                        'titles' => [],
                        'sourceFile' => [
                            'digitalAsset' => [],
                        ],
                    ],
                ],
                'types' => [],
            ],
            'isUpcoming' => [],
            'maximumQuantityToOrder' => [],
            'minimumQuantityToOrder' => [],
            'names' => [],
            'unitPrecisions' => [],
        ],
    ];

    /** @var array */
    private const FIELDS_FOR_KITS = [
        'kitItemLineItems' => [
            'kitItem' => [
                'labels' => [],
            ],
            'product' => self::FIELDS_FOR_SIMPLE['product'],
        ],
    ] + self::FIELDS_FOR_SIMPLE;

    /** @var array */
    private const FIELDS_FOR_CONFIGURABLE_WHEN_UNGROUPED = [
        'parentProduct' => [
            'names' => [],
        ],
    ] + self::FIELDS_FOR_SIMPLE;

    /** @var array */
    private const FIELDS_FOR_CONFIGURABLE_WHEN_GROUPED = [
        'parentProduct' => [
            'names' => [],
            'unitPrecisions' => [],
            'images' => [
                'image' => [
                    'digitalAsset' => [
                        'titles' => [],
                        'sourceFile' => [
                            'digitalAsset' => [],
                        ],
                    ],
                ],
                'types' => [],
            ],
        ],
    ] + self::FIELDS_FOR_SIMPLE;

    public function __construct(PreloadingManager $preloadingManager)
    {
        $this->preloadingManager = $preloadingManager;
    }

    public function onLineItemData(DatagridLineItemsDataEvent $event): void
    {
        $isGrouped = $event->getContext()['isGrouped'] ?? false;
        $simpleLineItems = [];
        $configurableLineItems = [];
        $kitLineItems = [];
        foreach ($event->getLineItems() as $lineItemId => $lineItem) {
            $lineItemType = $event->getDataForLineItem($lineItemId)['type'] ?? '';
            if ($lineItemType === Product::TYPE_CONFIGURABLE) {
                $configurableLineItems[] = $lineItem;
            } elseif ($lineItemType === Product::TYPE_KIT) {
                $kitLineItems[] = $lineItem;
            } else {
                $simpleLineItems[] = $lineItem;
            }
        }

        $this->preloadingManager->preloadInEntities($simpleLineItems, self::FIELDS_FOR_SIMPLE);
        $this->preloadingManager->preloadInEntities(
            $configurableLineItems,
            $isGrouped ? self::FIELDS_FOR_CONFIGURABLE_WHEN_GROUPED : self::FIELDS_FOR_CONFIGURABLE_WHEN_UNGROUPED
        );
        $this->preloadingManager->preloadInEntities($kitLineItems, self::FIELDS_FOR_KITS);
    }
}

<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
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
        $lineItems = $event->getLineItems();
        $lineItemsConfigurable = [];
        $lineItemsSimple = [];
        foreach ($lineItems as $lineItem) {
            if ($lineItem->getParentProduct()) {
                $lineItemsConfigurable[] = $lineItem;
            } else {
                $lineItemsSimple[] = $lineItem;
            }
        }

        $this->preloadingManager->preloadInEntities($lineItemsSimple, self::FIELDS_FOR_SIMPLE);
        $this->preloadingManager->preloadInEntities(
            $lineItemsConfigurable,
            $isGrouped ? self::FIELDS_FOR_CONFIGURABLE_WHEN_GROUPED : self::FIELDS_FOR_CONFIGURABLE_WHEN_UNGROUPED
        );
    }
}

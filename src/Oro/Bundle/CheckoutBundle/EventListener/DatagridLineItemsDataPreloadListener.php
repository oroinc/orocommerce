<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ProductBundle\EventListener\DatagridLineItemsDataPreloadListener as ParentPreloadListener;

/**
 * Preloads checkout line items.
 */
class DatagridLineItemsDataPreloadListener extends ParentPreloadListener
{
    /** @var array */
    protected const FIELDS_FOR_SIMPLE = [
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
        'kitItemLineItems' => [],
    ];

    /** @var array */
    protected const FIELDS_FOR_KITS = [
        'kitItemLineItems' => [
            'kitItem' => [
                'labels' => [],
                'productUnit' => [],
            ],
            'product' => [
                'names' => [],
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
                'unitPrecisions' => [],
            ],
            'productUnit' => [],
        ],
    ] + self::FIELDS_FOR_SIMPLE;

    /** @var array */
    protected const FIELDS_FOR_CONFIGURABLE_WHEN_UNGROUPED = [
        'parentProduct' => [
            'names' => [],
        ],
    ] + self::FIELDS_FOR_SIMPLE;

    /** @var array */
    protected const FIELDS_FOR_CONFIGURABLE_WHEN_GROUPED = [
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
}

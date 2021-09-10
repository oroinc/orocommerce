<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\AbstractLoadSegmentData;

class LoadSegmentData extends AbstractLoadSegmentData
{
    const PRODUCT_STATIC_SEGMENT = 'product_static_segment';
    const PRODUCT_DYNAMIC_SEGMENT = 'product_dynamic_segment';
    const PRODUCT_DYNAMIC_EMPTY_SEGMENT = 'product_dynamic_empty_segment';

    /**
     * @var array
     */
    protected static $segments = [
        self::PRODUCT_STATIC_SEGMENT => [
            'name' => 'Product Static Segment',
            'description' => 'Product Static Segment Description',
            'entity' => Product::class,
            'type' => SegmentType::TYPE_STATIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'Label',
                        'name' => 'id',
                        'sorting' => ''
                    ]
                ],
                'filters' => []
            ]
        ],
        self::PRODUCT_DYNAMIC_SEGMENT => [
            'name' => 'Product Dynamic Segment',
            'description' => 'Product Dynamic Segment Description',
            'entity' => Product::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'id',
                        'name' => 'id',
                        'sorting' => null
                    ],
                    [
                        'func' => null,
                        'label' => 'sku',
                        'name' => 'sku',
                        'sorting' => null
                    ]
                ],
                'filters' => [
                    [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 0,
                                    'type' => 2
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        self::PRODUCT_DYNAMIC_EMPTY_SEGMENT => [
            'name' => 'Product Dynamic Empty Segment',
            'description' => 'Product Dynamic Empty Segment Description',
            'entity' => Product::class,
            'type' => SegmentType::TYPE_DYNAMIC,
            'definition' => [
                'columns' => [
                    [
                        'func' => null,
                        'label' => 'id',
                        'name' => 'id',
                        'sorting' => null
                    ],
                    [
                        'func' => null,
                        'label' => 'sku',
                        'name' => 'sku',
                        'sorting' => null
                    ]
                ],
                'filters' => [
                    [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 0,
                                    'type' => 3
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected function getSegmentsData(): array
    {
        return self::$segments;
    }
}

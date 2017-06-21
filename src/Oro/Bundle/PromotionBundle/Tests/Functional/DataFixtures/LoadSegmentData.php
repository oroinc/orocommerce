<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\AbstractLoadSegmentData;

class LoadSegmentData extends AbstractLoadSegmentData
{
    const PRODUCT_STATIC_SEGMENT = 'product_static_segment';

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
                'filters' =>[]
            ]
        ],
    ];

    protected function getSegmentsData(): array
    {
        return self::$segments;
    }
}

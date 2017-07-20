<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\ProductBundle\Entity\ProductImageType;

class LoadFeaturedProductData extends LoadProductImageData
{
    /**
     * @var array
     */
    protected static $products = [
        'product-1' => [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-2'=> [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-3'=> [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-6'=> [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-7'=> [
            'type' => ProductImageType::TYPE_LISTING
        ],
        'product-8'=> [
            'type' => ProductImageType::TYPE_LISTING
        ],
    ];
}

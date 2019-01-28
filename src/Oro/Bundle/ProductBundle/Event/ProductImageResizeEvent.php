<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ProductImageResizeEvent extends Event
{
    const NAME = 'oro_product.product_image.resize';

    /**
     * @var int
     */
    protected $productImageId;

    /**
     * @var bool
     */
    protected $forceOption;

    /**
     * @param int $productImageId
     * @param bool $forceOption
     */
    public function __construct($productImageId, $forceOption = false)
    {
        $this->productImageId = $productImageId;
        $this->forceOption = $forceOption;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'productImageId' => $this->productImageId,
            'force' => $this->forceOption
        ];
    }
}

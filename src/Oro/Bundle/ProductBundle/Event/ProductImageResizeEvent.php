<?php

namespace Oro\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event holding data for image resizing
 */
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
     * @var array|null
     */
    protected $dimensions;

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
            'force' => $this->forceOption,
            'dimensions' => $this->dimensions
        ];
    }

    /**
     * @param array|null $dimensions
     */
    public function setDimensions(array $dimensions = null)
    {
        $this->dimensions = $dimensions;
    }
}

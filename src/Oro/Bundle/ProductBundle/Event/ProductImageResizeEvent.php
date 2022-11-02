<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Symfony\Contracts\EventDispatcher\Event;

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
     * @param array|null $dimensions
     */
    public function __construct($productImageId, $forceOption = false, array $dimensions = null)
    {
        $this->productImageId = $productImageId;
        $this->forceOption = $forceOption;
        $this->dimensions = $dimensions;
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

    public function getTopicName(): string
    {
        return ResizeProductImageTopic::getName();
    }
}

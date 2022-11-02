<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Model\ProductView;

/**
 * Contains data for event that is raised during building a product list result.
 */
class BuildResultProductListEvent extends ProductListEvent
{
    public const NAME = 'oro_product.product_list.build_result';

    /** @var array [product id => [field name => field value, ...], ...] */
    private array $productData;

    /** @var ProductView[] [product id => product view, ...] */
    private array $productViews;

    public function __construct(string $productListType, array $productData, array $productViews)
    {
        parent::__construct($productListType);
        $this->productData = $productData;
        $this->productViews = $productViews;
    }

    /**
     * @return array [product id => [field name => field value, ...], ...]
     */
    public function getProductData(): array
    {
        return $this->productData;
    }

    /**
     * @return ProductView[] [product id => product view, ...]
     */
    public function getProductViews(): array
    {
        return $this->productViews;
    }

    public function getProductView(int $productId): ProductView
    {
        if (!isset($this->productViews[$productId])) {
            throw new \InvalidArgumentException(sprintf(
                'A product view does not exist. Product ID: %d.',
                $productId
            ));
        }

        return $this->productViews[$productId];
    }
}

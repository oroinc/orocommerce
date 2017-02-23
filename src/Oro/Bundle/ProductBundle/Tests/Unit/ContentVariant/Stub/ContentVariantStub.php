<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\WebCatalog\Test\Unit\Form\Type\AbstractContentVariantStub;

class ContentVariantStub extends AbstractContentVariantStub
{
    /**
     * @var Product
     */
    protected $productPageProduct;

    /**
     * @return Product
     */
    public function getProductPageProduct()
    {
        return $this->productPageProduct;
    }

    /**
     * @param Product $productPageProduct
     * @return ContentVariantStub
     */
    public function setProductPageProduct($productPageProduct)
    {
        $this->productPageProduct = $productPageProduct;

        return $this;
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\WebCatalog\Test\Unit\Form\Type\AbstractContentVariantStub;

class ContentVariantStub extends AbstractContentVariantStub
{
    /**
     * @var Product
     */
    protected $productPageProduct;

    /**
     * @var Segment
     */
    protected $productCollectionSegment;

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

    /**
     * @return Segment
     */
    public function getProductCollectionSegment()
    {
        return $this->productCollectionSegment;
    }

    /**
     * @param Segment $productCollectionSegment
     * @return $this
     */
    public function setProductCollectionSegment(Segment $productCollectionSegment)
    {
        $this->productCollectionSegment = $productCollectionSegment;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

class SearchTermStub extends SearchTerm
{
    protected ?Product $redirectProduct = null;

    protected ?Segment $productCollectionSegment = null;

    public function __construct(?int $id = null)
    {
        parent::__construct();

        $this->id = $id;
    }

    public function getRedirectProduct(): ?Product
    {
        return $this->redirectProduct;
    }

    public function setRedirectProduct(?Product $product): self
    {
        $this->redirectProduct = $product;

        return $this;
    }

    public function getProductCollectionSegment(): ?Segment
    {
        return $this->productCollectionSegment;
    }

    public function setProductCollectionSegment(?Segment $productCollectionSegment): self
    {
        $this->productCollectionSegment = $productCollectionSegment;

        return $this;
    }
}

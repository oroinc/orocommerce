<?php

namespace Oro\Component\Duplicator\Test\Stub;

use Doctrine\Common\Collections\ArrayCollection;

class RequestProduct
{
    /**
     * @var ArrayCollection
     */
    protected $productItems;

    /**
     * @var string
     */
    protected $comment;

    public function __construct()
    {
        $this->productItems = new ArrayCollection();
    }

    /**
     * @param RequestProductItem $item
     */
    public function addRequestProductItem(RequestProductItem $item)
    {
        $this->productItems->add($item);
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return ArrayCollection|RequestProductItem[]
     */
    public function getProductItems()
    {
        return $this->productItems;
    }
}

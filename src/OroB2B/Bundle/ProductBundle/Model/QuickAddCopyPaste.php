<?php

namespace OroB2B\Bundle\ProductBundle\Model;

class QuickAddCopyPaste
{
    /**
     * @var QuickAddRowCollection
     */
    protected $collection;

    public function __construct()
    {
        $this->collection = new QuickAddRowCollection();
    }

    /**
     * @return QuickAddRowCollection
     */
    public function getCollection()
    {
        return $this->collection;
    }

    /**
     * @param QuickAddRowCollection $collection
     */
    public function setCollection(QuickAddRowCollection $collection)
    {
        $this->collection = $collection;
    }
}

<?php

namespace OroB2B\Bundle\ProductBundle\Model;

class QuickAddCopyPaste
{
    /**
     * @var QuickAddRowCollection
     */
    protected $collection;

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

<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Symfony\Contracts\EventDispatcher\Event;

class QuickAddRowCollectionValidateEvent extends Event
{
    const NAME = 'quick_add_row_collection.validate';

    /**
     * @var QuickAddRowCollection
     */
    protected $quickAddRowCollection;

    /**
     * @param QuickAddRowCollection $quickAddRowCollection
     */
    public function __construct(QuickAddRowCollection $quickAddRowCollection = null)
    {
        $this->quickAddRowCollection = $quickAddRowCollection;
    }

    /**
     * @return QuickAddRowCollection
     */
    public function getQuickAddRowCollection()
    {
        return $this->quickAddRowCollection;
    }

    /**
     * @param QuickAddRowCollection $quickAddRowCollection
     * @return $this
     */
    public function setQuickAddRowCollection(QuickAddRowCollection $quickAddRowCollection)
    {
        $this->quickAddRowCollection = $quickAddRowCollection;

        return $this;
    }
}

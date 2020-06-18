<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListStub extends ShoppingList
{
    /** @var ArrayCollection|CustomerVisitor[] */
    private $visitors;

    /**
     * ShoppingListStub constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->visitors = new ArrayCollection();
    }

    /**
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return ArrayCollection|CustomerVisitor[]
     */
    public function getVisitors()
    {
        return $this->visitors;
    }

    /**
     * @param CustomerVisitor $customerVisitor
     *
     * @return ShoppingListStub
     */
    public function addVisitor($customerVisitor)
    {
        if (!$this->getVisitors()->contains($customerVisitor)) {
            $this->getVisitors()->add($customerVisitor);
        }

        return $this;
    }

    /**
     * @param Collection $lineItems
     * @return $this
     */
    public function setLineItems(Collection $lineItems): self
    {
        $this->lineItems = $lineItems;

        return $this;
    }
}

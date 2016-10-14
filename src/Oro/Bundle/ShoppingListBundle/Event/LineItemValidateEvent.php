<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\EntityConfigBundle\Event\Event;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class LineItemValidateEvent extends Event
{
    const NAME = 'line_item.validate';

    /**
     * @var ArrayCollection
     */
    protected $errors;

    /**
     * @var
     */
    protected $lineItems;

    /**
     * @param LineItem[] $lineItems
     */
    public function __construct($lineItems)
    {
        $this->lineItems = $lineItems;
        $this->errors = new ArrayCollection();
    }

    /**
     * @return ArrayCollection
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $sku
     * @param string $message
     * @return $this
     */
    public function addError($sku, $message)
    {
        $this->errors->add(['sku' => $sku, 'message' => $message]);

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems()
    {
        return $this->lineItems;
    }

    /**
     * @param LineItem[] $lineItems
     *
     * @return $this
     */
    public function setLineItems($lineItems)
    {
        $this->lineItems = $lineItems;

        return $this;
    }
}

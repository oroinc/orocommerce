<?php

namespace Oro\Bundle\ShippingBundle\ExpressionLanguage;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\PropertyAccess\PropertyAccessor;

class OrderLineItemDecorator
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var Collection
     */
    protected $lineItems;

    /**
     * @var Collection
     */
    protected $lineItem;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    /**
     * @param Factory $factory
     * @param Collection $lineItems
     * @param OrderLineItem $lineItem
     */
    public function __construct(Factory $factory, Collection $lineItems, OrderLineItem $lineItem)
    {
        $this->factory = $factory;
        $this->lineItems = $lineItems;
        $this->lineItem = $lineItem;
    }

    /**
     * {@inheritdoc}
     */
    public function __get($name)
    {
        if ($name === 'product') {
            return $this->factory->createProductDecorator($this->lineItems, $this->lineItem->getProduct());
        }

        return $this->getPropertyAccessor()->getValue($this->lineItem, $name);
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (!static::$propertyAccessor) {
            static::$propertyAccessor = new PropertyAccessor();
        }

        return static::$propertyAccessor;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CheckoutLineItemsConverter
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * @param array $data
     * @return ArrayCollection|OrderLineItem[]
     */
    public function convert(array $data)
    {
        $result = new ArrayCollection();

        foreach ($data as $item) {
            $orderLineItem = new OrderLineItem();
            foreach ($item as $property => $value) {
                if (null !== $value && $this->propertyAccessor->isWritable($orderLineItem, $property)) {
                    $this->propertyAccessor->setValue($orderLineItem, $property, $value);
                }
            }

            $result->add($orderLineItem);
        }

        return $result;
    }
}

<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Puts data from an array to Order line items
 */
class CheckoutLineItemsConverter
{
    /**
     * @var \ReflectionClass
     */
    private $reflectionClass;

    /**
     * @param array $data
     * @return ArrayCollection|OrderLineItem[]
     * @throws \ReflectionException
     */
    public function convert(array $data)
    {
        $result = new ArrayCollection();
        foreach ($data as $item) {
            $orderLineItem = new OrderLineItem();
            foreach ($item as $property => $value) {
                if (null !== $value && $this->getReflectionClass()->hasProperty($property)) {
                    $methodName = $this->getSetterName($property);
                    $orderLineItem->{$methodName}($value);
                }
            }

            $result->add($orderLineItem);
        }

        return $result;
    }

    /**
     * @return \ReflectionClass
     * @throws \ReflectionException
     */
    private function getReflectionClass()
    {
        if (!$this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass(OrderLineItem::class);
        }

        return $this->reflectionClass;
    }

    /**
     * @param string $propertyName
     *
     * @return string
     */
    private function getSetterName($propertyName)
    {
        return 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
    }
}

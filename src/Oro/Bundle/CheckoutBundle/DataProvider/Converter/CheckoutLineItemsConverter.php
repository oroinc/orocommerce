<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * Puts data from an array to Order line items
 */
class CheckoutLineItemsConverter
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @var \ReflectionClass
     */
    protected $reflectionClass;

    /**
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(PropertyAccessor $propertyAccessor)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->reflectionClass = new \ReflectionClass(OrderLineItem::class);
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
                if (null !== $value && $this->reflectionClass->hasProperty($property)) {
                    $methodName = $this->getSetterName($property);
                    $orderLineItem->{$methodName}($value);
                }
            }

            $result->add($orderLineItem);
        }

        return $result;
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

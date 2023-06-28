<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * Puts data from an array to Order line items
 */
class CheckoutLineItemsConverter
{
    /** @var array<\ReflectionClass> */
    private array $reflectionClass;

    /**
     * @param array<int|string,array<string,mixed>> $data Line items data
     *  [
     *      [
     *          'product' => Product $product,
     *          'productUnit' => ProductUnit $productUnit,
     *          'quantity' => float 12.3456,
     *          // ...
     *      ],
     *      // ...
     *  ]
     *
     * @return Collection<OrderLineItem>
     */
    public function convert(array $data): Collection
    {
        $result = new ArrayCollection();
        foreach ($data as $lineItemData) {
            $kitItemLineItemsData = [];
            if (isset($lineItemData['kitItemLineItems']) && is_array($lineItemData['kitItemLineItems'])) {
                $kitItemLineItemsData = $lineItemData['kitItemLineItems'];
                unset($lineItemData['kitItemLineItems']);
            }

            /** @var OrderLineItem $orderLineItem */
            $orderLineItem = $this->hydrate(OrderLineItem::class, $lineItemData);

            foreach ($kitItemLineItemsData as $kitItemLineItemDatum) {
                $orderLineItem->addKitItemLineItem(
                    $this->hydrate(OrderProductKitItemLineItem::class, $kitItemLineItemDatum)
                );
            }

            $result->add($orderLineItem);
        }

        return $result;
    }

    private function hydrate(string $className, array $data): object
    {
        $object = new $className();

        foreach ($data as $property => $value) {
            if (null !== $value && $this->getReflectionClass($className)->hasProperty($property)) {
                $methodName = $this->getSetterName($property);
                $object->{$methodName}($value);
            }
        }

        return $object;
    }

    /**
     * @throws \ReflectionException
     */
    private function getReflectionClass(string $className): \ReflectionClass
    {
        if (!isset($this->reflectionClass[$className])) {
            $this->reflectionClass[$className] = new \ReflectionClass($className);
        }

        return $this->reflectionClass[$className];
    }

    private function getSetterName(string $propertyName): string
    {
        return 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
    }
}

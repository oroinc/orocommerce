<?php

namespace Oro\Bundle\CheckoutBundle\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * Puts data from an array to Order line items
 */
class CheckoutLineItemsConverter
{
    /** @var array<EntityReflectionClass> */
    private array $reflectionClass;

    private $exisingLineItems = [];

    private bool $reuseLineItems = false;

    public function setReuseLineItems(bool $reuseLineItems): void
    {
        $this->reuseLineItems = $reuseLineItems;
        $this->exisingLineItems = [];
    }

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
            $hash = $lineItemData['checksum'] ?? null;
            if ($this->reuseLineItems && array_key_exists($hash, $this->exisingLineItems)) {
                $orderLineItem = $this->exisingLineItems[$hash];
            } else {
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
                if ($this->reuseLineItems) {
                    $this->exisingLineItems[$hash] = $orderLineItem;
                }
            }
            $result->add($orderLineItem);
        }

        return $result;
    }

    private function hydrate(string $className, array $data): object
    {
        $object = new $className();

        foreach ($data as $property => $value) {
            $reflectionClass = $this->getReflectionClass($className);
            if (null !== $value && $reflectionClass->hasProperty($property)) {
                $methodName = $this->getSetterName($property);
                $reflectionClass
                    ->getMethod($methodName)
                    ->invoke($object, $value);
            }
        }

        return $object;
    }

    /**
     * @throws \ReflectionException
     */
    private function getReflectionClass(string $className): EntityReflectionClass
    {
        if (!isset($this->reflectionClass[$className])) {
            $this->reflectionClass[$className] = new EntityReflectionClass($className);
        }

        return $this->reflectionClass[$className];
    }

    private function getSetterName(string $propertyName): string
    {
        return 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $propertyName)));
    }
}

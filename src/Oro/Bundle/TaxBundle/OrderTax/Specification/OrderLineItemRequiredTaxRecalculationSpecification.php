<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * This specification specifies an OrderLineItem which taxes was not calculated
 * or OrderLineItem which was changed in a way that could lead to the different result of the tax calculation
 */
class OrderLineItemRequiredTaxRecalculationSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * @param OrderLineItem $orderLineItem
     *
     * @return bool
     */
    public function isSatisfiedBy($orderLineItem): bool
    {
        if (!$orderLineItem instanceof OrderLineItem) {
            return false;
        }

        if (!$orderLineItem->getId()) {
            return true;
        }

        $originalData = $this->getOriginalEntityData($orderLineItem);

        /**
         * If entity was not loaded it means no changes was made to the entity because it
         * is either proxy or reference
         */
        if (!$originalData) {
            return false;
        }

        if ($this->isProductChanged($orderLineItem, $originalData)) {
            return true;
        }

        if ($this->isProductUnitChanged($orderLineItem, $originalData)) {
            return true;
        }

        if ($this->isPriceChanged($orderLineItem, $originalData)) {
            return true;
        }

        return $this->isQuantityChanged($orderLineItem, $originalData);
    }

    private function isProductChanged(OrderLineItem $orderLineItem, array $originalData): bool
    {
        $newProductId = $orderLineItem->getProduct()
            ? $orderLineItem->getProduct()->getId()
            : null;
        $oldProductId = !empty($originalData['product'])
            ? $originalData['product']->getId()
            : null;

        return $newProductId != $oldProductId;
    }

    private function isProductUnitChanged(OrderLineItem $orderLineItem, array $originalData): bool
    {
        $newProductUnitCode = $orderLineItem->getProductUnit()
            ? $orderLineItem->getProductUnit()->getCode()
            : null;
        $oldProductUnitCode = !empty($originalData['productUnit'])
            ? $originalData['productUnit']->getCode()
            : null;

        return $newProductUnitCode != $oldProductUnitCode;
    }

    private function isPriceChanged(OrderLineItem $orderLineItem, array $originalData): bool
    {
        $newPrice = $orderLineItem->getPrice()
            ? $orderLineItem->getPrice()->getValue()
            : $orderLineItem->getValue();

        $originalPrice = $originalData['value'] ?? null;
        /**
         * Comparison should not be strict because value in `$originalOrderData` could be a string and
         * `$orderLineItem->getPrice()->getValue()` will return float
         */
        return $newPrice != $originalPrice;
    }

    private function isQuantityChanged(OrderLineItem $orderLineItem, array $originalData): bool
    {
        $originalQuantity = $originalData['quantity'] ?? null;

        return $orderLineItem->getQuantity() != $originalQuantity;
    }
}

<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * This specification specifies an OrderLineItem which taxes was not calculated
 * or OrderLineItem which was changed in a way that could lead to the different result of the tax calculation
 */
class OrderLineItemRequiredTaxRecalculationSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

    private SpecificationInterface $kitLineItemTaxRecalculationSpecification;

    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
        $this->kitLineItemTaxRecalculationSpecification = new OrderKitLineItemRequiredTaxRecalculationSpecification(
            $unitOfWork
        );
    }

    /**
     * @param OrderLineItem|object $orderLineItem
     */
    #[\Override]
    public function isSatisfiedBy(object $orderLineItem): bool
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

        if ($this->isQuantityChanged($orderLineItem, $originalData)) {
            return true;
        }

        return $this->isKitItemsCollectionChanged($orderLineItem->getKitItemLineItems());
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

    private function isKitItemsCollectionChanged(Collection $orderKitLineItems): bool
    {
        foreach ($orderKitLineItems as $kitLineItem) {
            if ($this->kitLineItemTaxRecalculationSpecification->isSatisfiedBy($kitLineItem)) {
                return true;
            }
        }

        return false;
    }
}

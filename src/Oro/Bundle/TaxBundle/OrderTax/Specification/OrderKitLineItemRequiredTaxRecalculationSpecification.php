<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;

/**
 * This specification specifies an OrderProductKitItemLineItem which taxes was not calculated or
 * OrderProductKitItemLineItem which was changed in a way that could lead to the different result of the tax calculation
 */
class OrderKitLineItemRequiredTaxRecalculationSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * @param OrderProductKitItemLineItem|object $orderLineItem
     */
    public function isSatisfiedBy(object $orderKitLineItem): bool
    {
        if (!$orderKitLineItem instanceof OrderProductKitItemLineItem) {
            return false;
        }

        if (!$orderKitLineItem->getId()) {
            return true;
        }

        $originalData = $this->getOriginalEntityData($orderKitLineItem);

        /**
         * If entity was not loaded it means no changes was made to the entity because it
         * is either proxy or reference
         */
        if (!$originalData) {
            return false;
        }

        if ($this->isPriceChanged($orderKitLineItem, $originalData)) {
            return true;
        }

        return $this->isQuantityChanged($orderKitLineItem, $originalData);
    }
}

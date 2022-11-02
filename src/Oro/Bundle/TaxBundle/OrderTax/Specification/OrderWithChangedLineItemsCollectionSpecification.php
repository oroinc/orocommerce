<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Specification;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;

/**
 * This specification specifies an Order which lineItemsCollection was changed, it means that
 * this specification will be satisfied if order line was removed or added or replaced
 */
class OrderWithChangedLineItemsCollectionSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

    /**
     * @var OrderLineItemRequiredTaxRecalculationSpecification
     */
    protected $specification;

    public function __construct(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function isSatisfiedBy($order): bool
    {
        if (!$order instanceof Order) {
            return false;
        }

        $originalOrderData = $this->getOriginalEntityData($order);
        /**
         * If entity was not loaded it means no changes was made
         */
        if (!$originalOrderData) {
            return false;
        }

        $originalCollectionValue = $originalOrderData['lineItems'] ?? null;
        if ($this->isCollectionChanged($order->getLineItems(), $originalCollectionValue)) {
            return true;
        }

        return false;
    }

    /**
     * Check if some item removed or added to the collection
     */
    protected function isCollectionChanged(Collection $orderLineItems, ?PersistentCollection $originalCollection): bool
    {
        if ($orderLineItems instanceof PersistentCollection) {
            return $orderLineItems->isDirty();
        }

        $getIdFromLineItemCallback = function (OrderLineItem $orderLineItem) {
            return $orderLineItem->getId();
        };

        if (null === $originalCollection) {
            return true;
        }

        $originalCollectionIds = array_map($getIdFromLineItemCallback, $originalCollection->toArray());
        $newCollectionIds = array_map($getIdFromLineItemCallback, $orderLineItems->toArray());

        return array_diff($originalCollectionIds, $newCollectionIds)
            || array_diff($newCollectionIds, $originalCollectionIds);
    }
}

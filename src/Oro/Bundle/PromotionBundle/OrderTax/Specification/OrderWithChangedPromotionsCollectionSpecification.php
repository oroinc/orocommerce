<?php

namespace Oro\Bundle\PromotionBundle\OrderTax\Specification;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OriginalDataAccessorTrait;
use Oro\Bundle\TaxBundle\OrderTax\Specification\SpecificationInterface;

/**
 * This specification specifies an Order which discounts collection was changed, it means that
 * this specification will be satisfied if promotion was removed or added or replaced
 */
class OrderWithChangedPromotionsCollectionSpecification implements SpecificationInterface
{
    use OriginalDataAccessorTrait;

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

        if (!$order->getId()) {
            return true;
        }

        $originalOrderData = $this->getOriginalEntityData($order);
        /**
         * If entity was not loaded it means no changes was made
         */
        if (!$originalOrderData) {
            return false;
        }

        return $this->isPromotionsChanged($order->getAppliedPromotions());
    }

    /**
     * @param Collection|array $appliedPromotions
     *
     * @return bool
     */
    private function isPromotionsChanged(Collection|array $appliedPromotions): bool
    {
        if ($appliedPromotions instanceof PersistentCollection && $appliedPromotions->isDirty()) {
            return true;
        }

        foreach ($appliedPromotions as $appliedPromotion) {
            $originalAppliedPromotion = $this->getOriginalEntityData($appliedPromotion);
            $originalIsActiveStatus = $originalAppliedPromotion['active'] ?? null;

            if ($appliedPromotion->isActive() !== $originalIsActiveStatus) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Synchronizes the order discounts between source and target order.
 */
class OrderDiscountAwareOrderDraftSynchronizer implements EntityDraftSynchronizerInterface
{
    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function synchronizeFromDraft(
        EntityDraftAwareInterface $draft,
        EntityDraftAwareInterface $entity,
    ): void {
        assert($draft instanceof Order);
        assert($entity instanceof Order);

        $this->synchronizeDiscounts($draft, $entity);
    }

    #[\Override]
    public function synchronizeToDraft(
        EntityDraftAwareInterface $entity,
        EntityDraftAwareInterface $draft,
    ): void {
        assert($entity instanceof Order);
        assert($draft instanceof Order);

        $this->synchronizeDiscounts($entity, $draft);
    }

    private function synchronizeDiscounts(Order $sourceOrder, Order $targetOrder): void
    {
        $sourceDiscounts = $sourceOrder->getDiscounts();
        $targetDiscounts = $targetOrder->getDiscounts();

        foreach ($targetDiscounts as $index => $targetDiscount) {
            if (!$sourceDiscounts->containsKey($index)) {
                $targetDiscounts->remove($index);
            }
        }

        foreach ($sourceDiscounts as $index => $sourceDiscount) {
            if ($targetDiscounts->containsKey($index)) {
                $targetDiscount = $targetDiscounts->get($index);
                $targetDiscount->setType($sourceDiscount->getType());
                $targetDiscount->setDescription($sourceDiscount->getDescription());
                $targetDiscount->setPercent($sourceDiscount->getPercent());
                $targetDiscount->setAmount($sourceDiscount->getAmount());
            } else {
                $newDiscount = new OrderDiscount();
                $newDiscount->setType($sourceDiscount->getType());
                $newDiscount->setDescription($sourceDiscount->getDescription());
                $newDiscount->setPercent($sourceDiscount->getPercent());
                $newDiscount->setAmount($sourceDiscount->getAmount());

                $targetOrder->addDiscount($newDiscount);
            }
        }
    }
}

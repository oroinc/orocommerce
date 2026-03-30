<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\DraftSession\Factory;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Component\DraftSession\Entity\EntityDraftAwareInterface;
use Oro\Component\DraftSession\Factory\EntityDraftFactoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;

/**
 * Creates an order draft from an existing order.
 */
class OrderDraftFactory implements EntityDraftFactoryInterface
{
    public function __construct(
        private readonly EntityDraftSynchronizerInterface $entityDraftSynchronizer,
        private readonly EntityDraftFactoryInterface $entityDraftFactory,
    ) {
    }

    #[\Override]
    public function supports(string $entityClass): bool
    {
        return $entityClass === Order::class;
    }

    #[\Override]
    public function createDraft(EntityDraftAwareInterface $entity, string $draftSessionUuid): Order
    {
        assert($entity instanceof Order);

        $orderDraft = new Order();
        $orderDraft->setDraftSessionUuid($draftSessionUuid);
        $orderDraft->setDraftSource($entity->getId() ? $entity : null);

        if (!$entity->getId()) {
            // Synchronizes line items to the order draft only for new orders.
            foreach ($entity->getLineItems() as $lineItem) {
                /** @var OrderLineItem $lineItemDraft */
                $lineItemDraft = $this->entityDraftFactory->createDraft($lineItem, $draftSessionUuid);
                $orderDraft->addLineItem($lineItemDraft);
            }
        }

        $this->entityDraftSynchronizer->synchronizeToDraft($entity, $orderDraft);

        return $orderDraft;
    }
}

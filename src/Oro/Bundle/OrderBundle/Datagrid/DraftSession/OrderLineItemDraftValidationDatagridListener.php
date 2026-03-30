<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Datagrid\DraftSession;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\Repository\OrderRepository;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GroupSequence;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Adds isValid property to the datagrid result to indicate whether the order line item is valid.
 * Prepends ordering by isValid column so invalid line items appear at the top.
 */
class OrderLineItemDraftValidationDatagridListener
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly OrderDraftManager $orderDraftManager,
        private readonly ValidatorInterface $validator,
        private readonly DraftSessionOrmFilterManager $draftSessionOrmFilterManager
    ) {
    }

    public function onResultBeforeQuery(OrmResultBeforeQuery $event): void
    {
        $isOrmFilterEnabled = $this->draftSessionOrmFilterManager->isEnabled();
        $this->draftSessionOrmFilterManager->disable();

        try {
            $draftSessionUuid = (string)$event->getDatagrid()->getParameters()->get('draft_session_uuid');
            $orderId = (int)$event->getDatagrid()->getParameters()->get('order_id');

            if (!$draftSessionUuid) {
                return;
            }

            $order = $this->getOrderForValidation($orderId, $draftSessionUuid);
            if (!$order) {
                return;
            }

            $invalidLineItems = $this->collectInvalidLineItems($order);
            $this->applyValidationToQueryBuilder($event->getQueryBuilder(), $invalidLineItems);
        } finally {
            if ($isOrmFilterEnabled) {
                $this->draftSessionOrmFilterManager->enable();
            }
        }
    }

    private function getOrderForValidation(int $orderId, string $draftSessionUuid): ?Order
    {
        // Dealing with existing order.
        if ($orderId) {
            /** @var OrderRepository $orderRepository */
            $orderRepository = $this->doctrine->getRepository(Order::class);
            $order = $orderRepository->getOrderWithRelations($orderId);
            if (!$order) {
                // Order does not exist anymore.
                return null;
            }
        }

        $orderDraft = $this->orderDraftManager->findOrderDraft($draftSessionUuid);
        if (!$orderDraft) {
            return $order ?? null;
        }

        $order ??= $orderDraft->getDraftSource() ?? new Order();
        $this->orderDraftManager->synchronizeEntityFromDraft($orderDraft, $order);

        return $order;
    }

    /**
     * @param Order $order
     *
     * @return array<int> Array of invalid line item IDs.
     */
    private function collectInvalidLineItems(Order $order): array
    {
        $validationGroups = new GroupSequence(
            [Constraint::DEFAULT_GROUP, $order->getId() ? 'order_update' : 'order_create']
        );

        $violations = $this->validator
            ->startContext($order)
            ->atPath('data')
            ->validate($order, null, $validationGroups)
            ->getViolations();

        $invalidLineItems = [];
        foreach ($violations as $violation) {
            $lineItemId = $this->extractLineItemIdFromViolation($violation, $order);
            if ($lineItemId !== null) {
                $invalidLineItems[$lineItemId] = $lineItemId;
            }
        }

        return array_values($invalidLineItems);
    }

    private function extractLineItemIdFromViolation(ConstraintViolationInterface $violation, Order $order): ?int
    {
        $propertyPath = new PropertyPath($violation->getPropertyPath());

        if ($propertyPath->getLength() < 3) {
            return null;
        }

        if ($propertyPath->getElement(1) !== 'lineItems') {
            // Skips if the violation is not related to order line items.
            return null;
        }

        /** @var OrderLineItem|null $lineItem */
        $lineItem = $order->getLineItems()->get($propertyPath->getElement(2));

        if ($lineItem?->getId()) {
            return $lineItem->getId();
        }

        // New line item may have a reference to its draft.
        $lineItemDraft = $lineItem->getDrafts()->first() ?: null;
        $lineItemDraftId = $lineItemDraft?->getId();

        if (!$lineItemDraftId) {
            throw new \LogicException('Entity draft is expected to be present for a new order line item.');
        }

        return $lineItemDraftId;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array<int> $invalidLineItems
     *
     * @return void
     */
    private function applyValidationToQueryBuilder(QueryBuilder $queryBuilder, array $invalidLineItems): void
    {
        if ($invalidLineItems) {
            $queryBuilder
                ->addSelect('CASE WHEN orderLineItem.id NOT IN (:invalidLineItems) THEN 1 ELSE 0 END AS isValid')
                ->setParameter('invalidLineItems', $invalidLineItems, Connection::PARAM_INT_ARRAY);

            $this->prependOrderByIsValid($queryBuilder);
        } else {
            $queryBuilder->addSelect('\'1\' AS isValid');
        }
    }

    private function prependOrderByIsValid(QueryBuilder $queryBuilder): void
    {
        // Prepends the ordering by isValid column to the existing order by list.
        $orderByDqlParts = $queryBuilder->getDQLPart('orderBy');
        $queryBuilder->resetDQLPart('orderBy');
        $queryBuilder->orderBy('isValid', 'ASC');

        foreach ($orderByDqlParts as $orderByDqlPart) {
            $queryBuilder->addOrderBy($orderByDqlPart);
        }
    }
}

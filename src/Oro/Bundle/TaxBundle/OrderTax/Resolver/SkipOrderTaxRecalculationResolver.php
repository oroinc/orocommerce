<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Resolver;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderLineItemRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\OrderTax\Specification\OrderRequiredTaxRecalculationSpecification;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;

/**
 * Resolver which should stop tax recalculation for Order and OrderLineItem entities which taxes
 * are already calculated. It should stop tax recalculation in case if entities has no changes which
 * could lead to the different result of tax calculation for them
 */
class SkipOrderTaxRecalculationResolver implements ResolverInterface
{
    /** @var ManagerRegistry */
    private ManagerRegistry $doctrine;

    /** @var TaxManager */
    private TaxManager $taxManager;

    /** @var FrontendHelper */
    private FrontendHelper $frontendHelper;

    /** @var array */
    private array $orderRequiresTaxRecalculation = [];

    /**
     * @param ManagerRegistry $doctrine
     * @param TaxManager $taxManager
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(ManagerRegistry $doctrine, TaxManager $taxManager, FrontendHelper $frontendHelper)
    {
        $this->doctrine = $doctrine;
        $this->taxManager = $taxManager;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getIdentifier() || !$taxable->getClassName()) {
            return;
        }

        if ($this->frontendHelper->isFrontendRequest()) {
            // Order tax recalculation check is not needed on store front.
            return;
        }

        /** @var EntityManager|null $entityManager */
        $entityManager = $this->doctrine->getManagerForClass($taxable->getClassName());
        if (!$entityManager) {
            return;
        }

        $uow = $entityManager->getUnitOfWork();
        $entity = $uow->tryGetById($taxable->getIdentifier(), $taxable->getClassName());
        if ($entity instanceof Order) {
            $this->resolveOrderTaxable($uow, $entity, $taxable);
        } elseif ($entity instanceof OrderLineItem) {
            $this->resolveOrderLineItemTaxable($uow, $entity);
        }
    }

    /**
     * @param UnitOfWork $uow
     * @param Order      $order
     * @param Taxable    $taxable
     *
     * @throws StopPropagationException
     * @throws TaxationDisabledException
     */
    private function resolveOrderTaxable(UnitOfWork $uow, Order $order, Taxable $taxable)
    {
        if ($this->isOrderTaxRecalculationRequired($order, $uow)) {
            // Recalculation is required.
            return;
        }

        $lineItemRequiredTaxRecalculationSpecification = new OrderLineItemRequiredTaxRecalculationSpecification($uow);
        foreach ($order->getLineItems() as $orderLineItem) {
            if ($lineItemRequiredTaxRecalculationSpecification->isSatisfiedBy($orderLineItem)) {
                // Recalculation is required.
                return;
            }
        }

        $taxResult = $taxable->getResult();
        /**
         * Tax items not always stored along with the order, so in some cases
         * we need to load them separately
         */
        if ($order->getLineItems() && !$taxResult->getItems()) {
            $itemsResult = [];
            foreach ($order->getLineItems() as $lineItem) {
                $itemsResult[] = $this->taxManager->loadTax($lineItem);
            }
            if ($itemsResult) {
                $taxResult->offsetSet(Result::ITEMS, $itemsResult);
            }
        }

        // Recalculation is not required.
        throw new StopPropagationException();
    }

    /**
     * @param UnitOfWork    $uow
     * @param OrderLineItem $orderLineItem
     * @throws StopPropagationException
     */
    private function resolveOrderLineItemTaxable(UnitOfWork $uow, OrderLineItem $orderLineItem)
    {
        if ($this->isOrderTaxRecalculationRequiredCached($orderLineItem->getOrder(), $uow)) {
            // Recalculation is required.
            return;
        }

        $lineItemRequiredTaxRecalculationSpecification = new OrderLineItemRequiredTaxRecalculationSpecification($uow);
        if ($lineItemRequiredTaxRecalculationSpecification->isSatisfiedBy($orderLineItem)) {
            // Recalculation is required.
            return;
        }

        // Recalculation is not required.
        throw new StopPropagationException();
    }

    /**
     * @param Order $order
     * @param UnitOfWork $uow
     * @return bool
     */
    private function isOrderTaxRecalculationRequiredCached(Order $order, UnitOfWork $uow): bool
    {
        $orderId = $order->getId();
        if (!$orderId) {
            return true;
        }

        if (!isset($this->orderRequiresTaxRecalculation[$orderId])) {
            $this->orderRequiresTaxRecalculation[$orderId] = $this->isOrderTaxRecalculationRequired($order, $uow);
        }

        return $this->orderRequiresTaxRecalculation[$orderId];
    }

    /**
     * @param Order $order
     * @param UnitOfWork $uow
     * @return bool
     */
    private function isOrderTaxRecalculationRequired(Order $order, UnitOfWork $uow): bool
    {
        $specification = new OrderRequiredTaxRecalculationSpecification($uow);

        return $specification->isSatisfiedBy($order);
    }

    /**
     * Clears local variable which is used for checking if order requires tax recalculation.
     */
    public function clearOrderRequiresTaxRecalculationCache(): void
    {
        $this->orderRequiresTaxRecalculation = [];
    }
}

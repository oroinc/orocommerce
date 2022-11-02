<?php

namespace Oro\Bundle\TaxBundle\OrderTax\Resolver;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\TaxBundle\Event\SkipOrderTaxRecalculationEvent;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\ResolverInterface;
use Oro\Bundle\TaxBundle\Resolver\StopPropagationException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Resolver which should stop tax recalculation for Order and OrderLineItem entities which taxes
 * are already calculated. It should stop tax recalculation in case if entities has no changes which
 * could lead to the different result of tax calculation for them
 */
class SkipOrderTaxRecalculationResolver implements ResolverInterface
{
    private ManagerRegistry $doctrine;

    private TaxManager $taxManager;

    private FrontendHelper $frontendHelper;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        ManagerRegistry $doctrine,
        TaxManager $taxManager,
        FrontendHelper $frontendHelper,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->doctrine = $doctrine;
        $this->taxManager = $taxManager;
        $this->frontendHelper = $frontendHelper;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     * @throws StopPropagationException
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
        if ($entity === false) {
            return;
        }

        $event = new SkipOrderTaxRecalculationEvent($taxable);

        $this->eventDispatcher->dispatch($event);

        if ($event->isSkipOrderTaxRecalculation()) {
            if ($entity instanceof Order) {
                $this->loadTaxItems($taxable, $entity);
            }

            // Recalculation is not required.
            throw new StopPropagationException();
        }
    }

    private function loadTaxItems($taxable, $order): void
    {
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
    }
}

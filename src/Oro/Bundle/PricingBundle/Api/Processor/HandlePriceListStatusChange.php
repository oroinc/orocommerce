<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles status change for price lists.
 */
class HandlePriceListStatusChange implements ProcessorInterface
{
    /** data structure: [price list id => [price list, initial status], ...] */
    private const PRICE_LIST_INITIAL_STATUSES = 'initial_statuses_for_price_lists';

    private PriceListRelationTriggerHandler $priceListChangesHandler;

    public function __construct(PriceListRelationTriggerHandler $priceListChangesHandler)
    {
        $this->priceListChangesHandler = $priceListChangesHandler;
    }

    /**
     * Adds the given price list and its initial status to the list of price list initial statuses.
     * This list is stored in shared data.
     */
    public static function addPriceListInitialStatus(
        SharedDataAwareContextInterface $context,
        PriceList $priceList,
        bool $initialStatus
    ): void {
        $sharedData = $context->getSharedData();
        $priceListStatuses = $sharedData->get(self::PRICE_LIST_INITIAL_STATUSES) ?? [];
        $priceListStatuses[$priceList->getId()] = [$priceList, $initialStatus];
        $sharedData->set(self::PRICE_LIST_INITIAL_STATUSES, $priceListStatuses);
    }

    /**
     * Moves price list initial statuses from shared data to the given context.
     */
    public static function movePriceListInitialStatusesToContext(SharedDataAwareContextInterface $context): void
    {
        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::PRICE_LIST_INITIAL_STATUSES)) {
            $context->set(self::PRICE_LIST_INITIAL_STATUSES, $sharedData->get(self::PRICE_LIST_INITIAL_STATUSES));
            $sharedData->remove(self::PRICE_LIST_INITIAL_STATUSES);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
        $priceListStatuses = $context->get(self::PRICE_LIST_INITIAL_STATUSES);
        /** @var PriceList $priceList */
        foreach ($priceListStatuses as [$priceList, $initialStatus]) {
            if ($priceList->isActive() !== $initialStatus) {
                $this->priceListChangesHandler->handlePriceListStatusChange($priceList);
            }
        }
        $context->remove(self::PRICE_LIST_INITIAL_STATUSES);
    }
}

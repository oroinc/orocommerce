<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Refreshes price lists that contain schedules.
 */
class UpdatePriceListsContainSchedule implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    private const PRICE_LISTS = 'price_lists_to_update_contain_schedule';

    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Adds the given price list to the list of price lists that require the price lists with schedules update.
     * This list is stored in shared data.
     */
    public static function addPriceListToUpdatePriceListsContainSchedule(
        SharedDataAwareContextInterface $context,
        PriceList $priceList
    ): void {
        $sharedData = $context->getSharedData();
        $priceLists = $sharedData->get(self::PRICE_LISTS) ?? [];
        $priceLists[$priceList->getId()] = $priceList;
        $sharedData->set(self::PRICE_LISTS, $priceLists);
    }

    /**
     * Moves price lists that require the price lists with schedules update from shared data to the given context.
     */
    public static function movePriceListsToUpdatePriceListsContainScheduleToContext(
        SharedDataAwareContextInterface $context
    ): void {
        $sharedData = $context->getSharedData();
        if ($sharedData->has(self::PRICE_LISTS)) {
            $context->set(self::PRICE_LISTS, $sharedData->get(self::PRICE_LISTS));
            $sharedData->remove(self::PRICE_LISTS);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var PriceList[] $priceLists */
        $priceLists = $context->get(self::PRICE_LISTS);
        foreach ($priceLists as $priceList) {
            $priceList->refreshContainSchedule();
        }
        $this->doctrineHelper->getEntityManagerForClass(PriceList::class)->flush();
        $context->remove(self::PRICE_LISTS);
    }
}

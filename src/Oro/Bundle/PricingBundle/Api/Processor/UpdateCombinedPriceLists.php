<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds combined price lists.
 */
class UpdateCombinedPriceLists implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    private const PRICE_LISTS = 'combined_price_lists_to_update';

    private CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder;

    public function __construct(CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder)
    {
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
    }

    /**
     * Adds the given price list to the list of price lists that require the combined price lists update.
     * This list is stored in shared data.
     */
    public static function addPriceListToUpdate(SharedDataAwareContextInterface $context, PriceList $priceList): void
    {
        $sharedData = $context->getSharedData();
        $priceLists = $sharedData->get(self::PRICE_LISTS) ?? [];
        $priceLists[$priceList->getId()] = $priceList;
        $sharedData->set(self::PRICE_LISTS, $priceLists);
    }

    /**
     * Moves price lists that require the combined price lists update from shared data to the given context.
     */
    public static function movePriceListsToUpdateToContext(SharedDataAwareContextInterface $context): void
    {
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
        $priceLists = $context->get(self::PRICE_LISTS);
        foreach ($priceLists as $priceList) {
            $this->combinedPriceListBuilder->buildByPriceList($priceList);
        }
        $context->remove(self::PRICE_LISTS);
    }
}

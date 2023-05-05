<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SharedDataAwareContextInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates lexemes for price lists.
 */
class UpdatePriceListLexemes implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    private const PRICE_LISTS = 'price_lists_to_update_lexemes';

    private PriceRuleLexemeHandler $priceRuleLexemeHandler;

    public function __construct(PriceRuleLexemeHandler $priceRuleLexemeHandler)
    {
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
    }

    /**
     * Adds the given price list to the list of price lists that require the lexemes update.
     * This list is stored in shared data.
     */
    public static function addPriceListToUpdateLexemes(
        SharedDataAwareContextInterface $context,
        PriceList $priceList
    ): void {
        $sharedData = $context->getSharedData();
        $priceLists = $sharedData->get(self::PRICE_LISTS) ?? [];
        $priceLists[$priceList->getId()] = $priceList;
        $sharedData->set(self::PRICE_LISTS, $priceLists);
    }

    /**
     * Moves price lists that require the lexemes update from shared data to the given context.
     */
    public static function movePriceListsToUpdateLexemesToContext(SharedDataAwareContextInterface $context): void
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
            $this->priceRuleLexemeHandler->updateLexemes($priceList);
        }
        $context->remove(self::PRICE_LISTS);
    }
}

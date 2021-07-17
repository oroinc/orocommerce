<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Updates lexemes for price lists.
 */
class UpdatePriceListLexemes implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    public const PRICE_LISTS = 'price_lists_to_update_lexemes';

    /** @var PriceRuleLexemeHandler */
    private $priceRuleLexemeHandler;

    public function __construct(PriceRuleLexemeHandler $priceRuleLexemeHandler)
    {
        $this->priceRuleLexemeHandler = $priceRuleLexemeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(self::PRICE_LISTS);
        foreach ($priceLists as $priceList) {
            $this->priceRuleLexemeHandler->updateLexemes($priceList);
        }
        $context->remove(self::PRICE_LISTS);
    }
}

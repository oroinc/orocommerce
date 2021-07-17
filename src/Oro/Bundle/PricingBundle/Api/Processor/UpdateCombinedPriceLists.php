<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Builds combined price lists.
 */
class UpdateCombinedPriceLists implements ProcessorInterface
{
    /** data structure: [price list id => price list, ...] */
    public const PRICE_LISTS = 'combined_price_lists_to_update';

    /** @var CombinedPriceListActivationPlanBuilder */
    private $combinedPriceListBuilder;

    public function __construct(CombinedPriceListActivationPlanBuilder $combinedPriceListBuilder)
    {
        $this->combinedPriceListBuilder = $combinedPriceListBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $priceLists = $context->get(self::PRICE_LISTS);
        foreach ($priceLists as $priceList) {
            $this->combinedPriceListBuilder->buildByPriceList($priceList);
        }
        $context->remove(self::PRICE_LISTS);
    }
}

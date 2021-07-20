<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Handles status change for price lists.
 */
class HandlePriceListStatusChange implements ProcessorInterface
{
    /** data structure: [price list id => [initial status, price list], ...] */
    public const PRICE_LIST_INITIAL_STATUSES = 'initial_statuses_for_price_lists';

    /** @var PriceListRelationTriggerHandler */
    private $priceListChangesHandler;

    public function __construct(PriceListRelationTriggerHandler $priceListChangesHandler)
    {
        $this->priceListChangesHandler = $priceListChangesHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        /** @var PriceList $priceList */
        $priceListStatuses = $context->get(self::PRICE_LIST_INITIAL_STATUSES);
        foreach ($priceListStatuses as list($initialStatus, $priceList)) {
            if ($priceList->isActive() !== $initialStatus) {
                $this->priceListChangesHandler->handlePriceListStatusChange($priceList);
            }
        }
        $context->remove(self::PRICE_LIST_INITIAL_STATUSES);
    }
}

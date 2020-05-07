<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sends all pricing related scheduled messages to the message queue.
 */
class BatchSendPricingScheduledMessages implements ProcessorInterface
{
    /** @var PriceListRelationTriggerHandler */
    private $priceListChangesHandler;

    /** @var PriceListTriggerHandler */
    private $priceListTriggerHandler;

    /**
     * @param PriceListRelationTriggerHandler $priceListChangesHandler
     * @param PriceListTriggerHandler         $priceListTriggerHandler
     */
    public function __construct(
        PriceListRelationTriggerHandler $priceListChangesHandler,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->priceListChangesHandler = $priceListChangesHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $this->priceListChangesHandler->sendScheduledTriggers();
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }
}

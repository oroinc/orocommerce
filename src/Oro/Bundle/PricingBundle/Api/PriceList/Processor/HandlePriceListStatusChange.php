<?php

namespace Oro\Bundle\PricingBundle\Api\PriceList\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class HandlePriceListStatusChange implements ProcessorInterface
{
    /**
     * @var PriceListRelationTriggerHandler
     */
    private $priceListChangesHandler;

    /**
     * @var bool|null
     */
    private $previousStatus;

    /**
     * @param PriceListRelationTriggerHandler $priceListChangesHandler
     */
    public function __construct(PriceListRelationTriggerHandler $priceListChangesHandler)
    {
        $this->priceListChangesHandler = $priceListChangesHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $priceList = $context->getResult();
        if (!$priceList instanceof PriceList) {
            return;
        }

        if ($this->previousStatus === null) {
            $this->previousStatus = $priceList->isActive();

            return;
        }

        if ($priceList->isActive() !== $this->previousStatus) {
            $this->priceListChangesHandler->handlePriceListStatusChange($priceList);
        }

        $this->previousStatus = null;
    }
}

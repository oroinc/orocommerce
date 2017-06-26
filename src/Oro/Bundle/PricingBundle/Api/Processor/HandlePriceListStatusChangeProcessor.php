<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class HandlePriceListStatusChangeProcessor implements ProcessorInterface
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
        /** @var PriceList $data */
        $data = $context->getResult();
        if (!$data) {
            return;
        }

        if ($this->previousStatus === null) {
            $this->previousStatus = $data->isActive();

            return;
        }

        if ($data->isActive() !== $this->previousStatus) {
            $this->priceListChangesHandler->handlePriceListStatusChange($data);
        }
    }
}

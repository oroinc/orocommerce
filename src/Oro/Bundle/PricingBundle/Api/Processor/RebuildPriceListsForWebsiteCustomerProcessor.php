<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class RebuildPriceListsForWebsiteCustomerProcessor implements ProcessorInterface
{
    /**
     * @var PriceListRelationTriggerHandler
     */
    private $relationChangesHandler;

    /**
     * @param PriceListRelationTriggerHandler $relationChangesHandler
     */
    public function __construct(PriceListRelationTriggerHandler $relationChangesHandler)
    {
        $this->relationChangesHandler = $relationChangesHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context)
    {
        $websiteCustomerAwareEntities = $context->getResult();

        if (!$websiteCustomerAwareEntities) {
            return;
        }

        if (!is_array($websiteCustomerAwareEntities)) {
            $websiteCustomerAwareEntities = [$websiteCustomerAwareEntities];
        }

        $processed = [];
        /** @var WebsiteAwareInterface|CustomerAwareInterface $entity */
        foreach ($websiteCustomerAwareEntities as $entity) {
            $customer = $entity->getCustomer();
            $website = $entity->getWebsite();

            if (isset($processed[$customer->getId()][$website->getId()])) {
                continue;
            }

            $this->relationChangesHandler->handleCustomerChange($customer, $website);
            $processed[$customer->getId()][$website->getId()] = true;
        }
    }
}

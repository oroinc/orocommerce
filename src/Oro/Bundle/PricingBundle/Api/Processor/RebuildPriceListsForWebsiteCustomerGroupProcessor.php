<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroupAwareInterface;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class RebuildPriceListsForWebsiteCustomerGroupProcessor implements ProcessorInterface
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
        $websiteCustomerGroupAwareEntities = $context->getResult();

        if (!$websiteCustomerGroupAwareEntities) {
            return;
        }

        if (!is_array($websiteCustomerGroupAwareEntities)) {
            $websiteCustomerGroupAwareEntities = [$websiteCustomerGroupAwareEntities];
        }

        $processed = [];
        /** @var WebsiteAwareInterface|CustomerGroupAwareInterface $entity */
        foreach ($websiteCustomerGroupAwareEntities as $entity) {
            $group = $entity->getCustomerGroup();
            $website = $entity->getWebsite();

            if (isset($processed[$group->getId()][$website->getId()])) {
                continue;
            }

            $this->relationChangesHandler->handleCustomerGroupChange($group, $website);
            $processed[$group->getId()][$website->getId()] = true;
        }
    }
}

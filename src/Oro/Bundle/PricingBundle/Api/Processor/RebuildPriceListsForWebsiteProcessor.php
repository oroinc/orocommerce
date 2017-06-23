<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class RebuildPriceListsForWebsiteProcessor implements ProcessorInterface
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
        $websiteAwareEntities = $context->getResult();

        if (!$websiteAwareEntities) {
            return;
        }

        if (!is_array($websiteAwareEntities)) {
            $websiteAwareEntities = [$websiteAwareEntities];
        }

        foreach ($this->getUniqueWebsites($websiteAwareEntities) as $website) {
            $this->relationChangesHandler->handleWebsiteChange($website);
        }
    }

    /**
     * @param WebsiteAwareInterface[] $websiteAwareEntities
     *
     * @return Website[]
     */
    private function getUniqueWebsites(array $websiteAwareEntities): array
    {
        $websites = [];
        foreach ($websiteAwareEntities as $entity) {
            $website = $entity->getWebsite();

            if (!array_key_exists($website->getId(), $websites)) {
                $websites[$website->getId()] = $website;
            }
        }

        return $websites;
    }
}

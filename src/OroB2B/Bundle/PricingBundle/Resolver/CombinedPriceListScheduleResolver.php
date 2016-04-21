<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;

class CombinedPriceListScheduleResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function updateRelations()
    {
        $ruleRepo = $this->registry->getManager()->getRepository('OroB2BPricingBundle:CombinedPriceListActivationRule');
        $newRulesToApply = $ruleRepo->updateActiveRule(new \DateTime());
        $entities = [
            'OroB2BPricingBundle:CombinedPriceListToAccount',
            'OroB2BPricingBundle:CombinedPriceListToAccountGroup',
            'OroB2BPricingBundle:CombinedPriceListToWebsite',
        ];
        if ($newRulesToApply) {
            foreach ($entities as $entity) {
                $repo = $this->registry->getManager()->getRepository($entity);
                /** @var PriceListToAccountRepository $repo */
                $repo->updateActuality($newRulesToApply);
            }
        }

    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\Entity\Repository\BasicCombinedRelationRepositoryTrait;

class CombinedPriceListScheduleResolver
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var string[]
     */
    protected $relationClasses = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \DateTime $time
     */
    public function updateRelations(\DateTime $time = null)
    {
        $rulesManager = $this->registry->getManagerForClass('OroB2BPricingBundle:CombinedPriceListActivationRule');
        $ruleRepo = $rulesManager->getRepository('OroB2BPricingBundle:CombinedPriceListActivationRule');
        if (!$time) {
            $time = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $ruleRepo->deleteExpiredRules($time);
        $newRulesToApply = $ruleRepo->getNewActualRules($time);
        if ($newRulesToApply) {
            foreach ($this->relationClasses as $className => $val) {
                /** @var BasicCombinedRelationRepositoryTrait $repo */
                $repo = $this->registry->getManagerForClass($className)->getRepository($className);
                $repo->updateActuality($newRulesToApply);
            }
            foreach ($newRulesToApply as $rule) {
                $rule->setActive(true);
            }
            $rulesManager->flush();
        }
    }

    /**
     * @param string $class
     */
    public function addRelationClass($class)
    {
        $this->relationClasses[$class] = true;
    }
}

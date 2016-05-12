<?php

namespace OroB2B\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PricingBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList;
use OroB2B\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use OroB2B\Bundle\PricingBundle\Entity\Repository\BasicCombinedRelationRepositoryTrait;
use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;

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
     * @var CombinedPriceListActivationRuleRepository
     */
    protected $activationRulesRepository;

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * @param \DateTime $time
     */
    public function updateRelations(\DateTime $time = null)
    {
        if (!$time) {
            $time = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $this->getCombinedPriceListActivationRuleRepository()->deleteExpiredRules($time);
        $newRulesToApply = $this->getCombinedPriceListActivationRuleRepository()->getNewActualRules($time);
        if ($newRulesToApply) {
            foreach ($this->relationClasses as $className => $val) {
                /** @var BasicCombinedRelationRepositoryTrait $repo */
                $repo = $this->registry->getManagerForClass($className)->getRepository($className);
                $repo->updateActuality($newRulesToApply);
            }
            $this->getCombinedPriceListActivationRuleRepository()->updateRulesActivity($newRulesToApply, true);
        }
        $this->updateCombinedPriceListConnection();
    }

    /**
     * @param CombinedPriceList $fullCPl
     * @param \DateTime|null $time
     * @return null|CombinedPriceList
     */
    public function getActiveCplByFullCPL(CombinedPriceList $fullCPl, \DateTime $time = null)
    {
        if (!$time) {
            $time = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $activeRule = $this->getCombinedPriceListActivationRuleRepository()->getActualRuleByCpl($fullCPl, $time);
        if ($activeRule) {
            return $activeRule->getCombinedPriceList();
        }

        return null;
    }

    /**
     * @param string $class
     */
    public function addRelationClass($class)
    {
        $this->relationClasses[$class] = true;
    }

    protected function updateCombinedPriceListConnection()
    {
        $fullCPLConfigKey = Configuration::getConfigKeyToFullPriceList();
        $currentCPLConfigKey = Configuration::getConfigKeyToPriceList();
        $fullCPLId = $this->configManager->get($fullCPLConfigKey);
        /** @var CombinedPriceListActivationRule $currentRule */
        if ($fullCPLId) {
            $currentRule = $this->getCombinedPriceListActivationRuleRepository()->findOneBy([
                'fullChainPriceList' => $fullCPLId,
                'active' => true,
            ]);
            if ($currentRule) {
                $currentFullCplId = (int)$this->configManager->get($currentCPLConfigKey);
                if ($currentFullCplId !== $currentRule->getCombinedPriceList()->getId()) {
                    $this->configManager->set($currentCPLConfigKey, $currentRule->getCombinedPriceList()->getId());
                }
            } else {
                $this->configManager->set($currentCPLConfigKey, (int)$fullCPLId);
            }
        } else {
            $this->configManager->set($currentCPLConfigKey, $fullCPLId);
        }
        $this->configManager->flush();
    }

    /**
     * @return CombinedPriceListActivationRuleRepository
     */
    protected function getCombinedPriceListActivationRuleRepository()
    {
        if (!$this->activationRulesRepository) {
            $className = 'OroB2BPricingBundle:CombinedPriceListActivationRule';
            $rulesManager = $this->registry->getManagerForClass($className);
            $this->activationRulesRepository = $rulesManager->getRepository($className);
        }
        return $this->activationRulesRepository;
    }
}

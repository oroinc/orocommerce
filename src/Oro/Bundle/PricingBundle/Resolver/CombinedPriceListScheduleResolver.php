<?php

namespace Oro\Bundle\PricingBundle\Resolver;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceListActivationRule;
use Oro\Bundle\PricingBundle\Entity\Repository\BasicCombinedRelationRepositoryTrait;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListActivationRuleRepository;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTriggerHandler;

class CombinedPriceListScheduleResolver
{
    /**
     * @var CombinedPriceListTriggerHandler
     */
    protected $triggerHandler;

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
     * @param CombinedPriceListTriggerHandler $triggerHandler
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        CombinedPriceListTriggerHandler $triggerHandler
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->triggerHandler = $triggerHandler;
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
        $this->triggerHandler->startCollect();
        $this->updateCombinedPriceListConnection();
        foreach ($newRulesToApply as $rule) {
            $this->triggerHandler->process($rule->getCombinedPriceList());
        }
        $this->triggerHandler->commit();
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
                    $this->triggerHandler->process($currentRule->getCombinedPriceList());
                }
            } else {
                $currentCPL = $this->registry->getManagerForClass(CombinedPriceList::class)
                    ->find(CombinedPriceList::class, (int)$fullCPLId);
                if ($currentCPL) {
                    $this->configManager->set($currentCPLConfigKey, (int)$fullCPLId);
                    $this->triggerHandler->process($currentCPL);
                } else {
                    $this->configManager->set($currentCPLConfigKey, null);
                }
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
            $className = 'OroPricingBundle:CombinedPriceListActivationRule';
            $rulesManager = $this->registry->getManagerForClass($className);
            $this->activationRulesRepository = $rulesManager->getRepository($className);
        }
        return $this->activationRulesRepository;
    }
}

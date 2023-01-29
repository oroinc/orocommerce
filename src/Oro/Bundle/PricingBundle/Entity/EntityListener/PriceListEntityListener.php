<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceListAssignedProductsTopic;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;

/**
 * Handle price list changes, schedule dependent price lists recalculation and combined price lists rebuild.
 */
class PriceListEntityListener
{
    const FIELD_PRODUCT_ASSIGNMENT_RULE = 'productAssignmentRule';
    const ACTIVE = 'active';

    /** @var PriceListRelationTriggerHandler */
    protected $triggerHandler;

    /** @var RuleCache */
    protected $cache;

    /** @var PriceListTriggerHandler */
    protected $priceListTriggerHandler;

    /** @var PriceRuleLexemeTriggerHandler */
    protected $priceRuleLexemeTriggerHandler;

    public function __construct(
        PriceListRelationTriggerHandler $triggerHandler,
        RuleCache $cache,
        PriceListTriggerHandler $priceListTriggerHandler,
        PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler
    ) {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
    }

    /**
     * Recalculate product assignments and price rules on product assignment rule change or price list activation.
     */
    public function preUpdate(PriceList $priceList, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_PRODUCT_ASSIGNMENT_RULE)) {
            $this->clearAssignmentRuleCache($priceList);
            $this->triggerPriceListRecalculation($priceList);
            $this->scheduleDependentPriceListsUpdate($priceList);
        }

        if ($event->hasChangedField(self::ACTIVE)) {
            $this->triggerPriceListRecalculation($priceList);
        }
    }

    public function preRemove(PriceList $priceList, LifecycleEventArgs $args)
    {
        // Remove product assignment rule and price rule caches
        $this->clearAssignmentRuleCache($priceList);
        foreach ($priceList->getPriceRules() as $priceRule) {
            $this->clearPriceRuleCache($priceRule);
        }

        // Recalculate Combined Price Lists
        $this->triggerHandler->handlePriceListStatusChange($priceList);

        // Schedule dependent price lists recalculation
        $this->scheduleDependentPriceListsUpdate($priceList);

        // Remove Combined Price Lists that lost PL from the chain
        $em = $args->getObjectManager();
        $cplRepo = $em->getRepository(CombinedPriceList::class);
        foreach ($cplRepo->getCombinedPriceListsByPriceList($priceList) as $affectedCpl) {
            $em->remove($affectedCpl);
        }
    }

    public function postPersist(PriceList $priceList)
    {
        if ($priceList->getProductAssignmentRule()) {
            $this->triggerPriceListRecalculation($priceList);
        }
    }

    protected function clearAssignmentRuleCache(PriceList $priceList)
    {
        $this->cache->delete('ar_' . $priceList->getId());
    }

    protected function clearPriceRuleCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }

    protected function scheduleDependentPriceListsUpdate(PriceList $priceList)
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            [self::FIELD_PRODUCT_ASSIGNMENT_RULE],
            $priceList->getId()
        );

        if (count($lexemes) > 0) {
            $dependentPriceLists = [];
            foreach ($lexemes as $lexeme) {
                $dependentPriceList = $lexeme->getPriceList();
                $dependentPriceLists[$dependentPriceList->getId()] = $dependentPriceList;

                if ($lexeme->getPriceRule()) {
                    $this->clearPriceRuleCache($lexeme->getPriceRule());
                } else {
                    $this->clearAssignmentRuleCache($dependentPriceList);
                }
            }
            $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes);

            foreach ($dependentPriceLists as $dependentPriceList) {
                $this->scheduleDependentPriceListsUpdate($dependentPriceList);
            }
        }
    }

    protected function triggerPriceListRecalculation(PriceList $priceList): void
    {
        // Skip processing of inactive Price Lists
        if (!$priceList->isActive()) {
            return;
        }

        $priceList->setActual(false);
        $this->priceListTriggerHandler->handlePriceListTopic(
            ResolvePriceListAssignedProductsTopic::getName(),
            $priceList
        );
    }
}

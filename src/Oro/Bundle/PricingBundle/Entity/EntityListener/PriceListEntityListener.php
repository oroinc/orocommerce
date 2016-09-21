<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;

class PriceListEntityListener
{
    const FIELD_PRODUCT_ASSIGNMENT_RULE = 'productAssignmentRule';

    /**
     * @var PriceListRelationTriggerHandler
     */
    protected $triggerHandler;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var PriceRuleLexemeTriggerHandler
     */
    protected $priceRuleLexemeTriggerHandler;

    /**
     * @param PriceListRelationTriggerHandler $triggerHandler
     * @param Cache $cache
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler
     */
    public function __construct(
        PriceListRelationTriggerHandler $triggerHandler,
        Cache $cache,
        PriceListTriggerHandler $priceListTriggerHandler,
        PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler
    ) {
        $this->triggerHandler = $triggerHandler;
        $this->cache = $cache;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
    }

    /**
     * Recalculate product assignments and price rules on product assignment rule change.
     *
     * @param PriceList $priceList
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceList $priceList, PreUpdateEventArgs $event)
    {
        if ($event->hasChangedField(self::FIELD_PRODUCT_ASSIGNMENT_RULE)) {
            $this->clearAssignmentRuleCache($priceList);
            $priceList->setActual(false);
            $this->priceListTriggerHandler
                ->addTriggerForPriceList(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $priceList);

            $this->scheduleDependentPriceListsUpdate($priceList);
        }
    }

    /**
     * @param PriceList $priceList
     */
    public function preRemove(PriceList $priceList)
    {
        // Remove caches
        $this->clearAssignmentRuleCache($priceList);
        foreach ($priceList->getPriceRules() as $priceRule) {
            $this->clearPriceRuleCache($priceRule);
        }

        // Recalculate Combined Price Lists
        $this->triggerHandler->handleFullRebuild();

        // Schedule dependent price lists recalculation
        $this->scheduleDependentPriceListsUpdate($priceList);
    }

    /**
     * @param PriceList $priceList
     */
    public function prePersist(PriceList $priceList)
    {
        if ($priceList->getProductAssignmentRule()) {
            $priceList->setActual(false);
            $this->priceListTriggerHandler
                ->addTriggerForPriceList(Topics::RESOLVE_PRICE_LIST_ASSIGNED_PRODUCTS, $priceList);
        }
    }

    /**
     * @param PriceList $priceList
     */
    protected function clearAssignmentRuleCache(PriceList $priceList)
    {
        $this->cache->delete('ar_' . $priceList->getId());
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearPriceRuleCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }

    /**
     * @param PriceList $priceList
     */
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
            $this->priceRuleLexemeTriggerHandler->addTriggersByLexemes($lexemes);

            foreach ($dependentPriceLists as $dependentPriceList) {
                $this->scheduleDependentPriceListsUpdate($dependentPriceList);
            }
        }
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;

class PriceRuleEntityListener
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var PriceRuleTriggerFiller
     */
    protected $priceRuleTriggersFiller;

    /**
     * @param Cache $cache
     * @param PriceRuleTriggerFiller $priceRuleTriggersFiller
     */
    public function __construct(Cache $cache, PriceRuleTriggerFiller $priceRuleTriggersFiller)
    {
        $this->cache = $cache;
        $this->priceRuleTriggersFiller = $priceRuleTriggersFiller;
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function postPersist(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
        $this->priceRuleTriggersFiller->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function preUpdate(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
        $this->priceRuleTriggersFiller->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * Recalculate price rules on price rule remove.
     *
     * @param PriceRule $priceRule
     */
    public function preRemove(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
        $this->priceRuleTriggersFiller->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }
}

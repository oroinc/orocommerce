<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\Common\Cache\Cache;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;

class PriceRuleEntityListener
{
    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param Cache $cache
     */
    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param PriceRule $priceRule
     */
    public function postUpdate(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
    }

    /**
     * @param PriceRule $priceRule
     */
    public function preRemove(PriceRule $priceRule)
    {
        $this->clearCache($priceRule);
    }

    /**
     * @param PriceRule $priceRule
     */
    protected function clearCache(PriceRule $priceRule)
    {
        $this->cache->delete('pr_' . $priceRule->getId());
    }
}

<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

class ShippingRuleChangeListener
{

    /** @var  ShippingPriceCache */
    private $priceCache;

    /**
     * @param ShippingPriceCache $priceCache
     */
    public function __construct(ShippingPriceCache $priceCache)
    {
        $this->priceCache = $priceCache;
    }

    /**
     * @param Rule $rule
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(Rule $rule, LifecycleEventArgs $args)
    {
        if ($this->isShippingRule($rule, $args)) {
            $this->priceCache->deleteAllPrices();
        }
    }
    
    /**
     * @param Rule $rule
     * @param LifecycleEventArgs $args
     * @return boolean
     */
    protected function isShippingRule(Rule $rule, LifecycleEventArgs $args)
    {
        $repository = $args->getEntityManager()->getRepository(ShippingMethodsConfigsRule::class);
        if ($repository->findOneBy(['rule' => $rule])) {
            return true;
        }
        return false;
    }
}

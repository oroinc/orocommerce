<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Cache;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

class ShippingRuleChangeListener
{

    /**
     * @var  ShippingPriceCache
     */
    private $priceCache;

    /**
     * @var  boolean
     */
    private $executed = false;

    /**
     * @param ShippingPriceCache $priceCache
     */
    public function __construct(ShippingPriceCache $priceCache)
    {
        $this->priceCache = $priceCache;
    }

    /**
     * @param Rule|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postPersist($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
    }

    /**
     * @param Rule|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
    }

    /**
     * @param Rule|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postRemove($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
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

    /**
     * @param Rule|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    protected function invalidateCache($entity, LifecycleEventArgs $args)
    {
        if(!$this->executed) {
            if (!$entity instanceof Rule) {
                $this->priceCache->deleteAllPrices();
                $this->executed = true;
            } else if ($this->isShippingRule($entity, $args)) {
                $this->priceCache->deleteAllPrices();
                $this->executed = true;
            }
        }
    }
}

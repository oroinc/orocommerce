<?php

namespace Oro\Bundle\ShippingBundle\EventListener\Cache;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\RuleBundle\Entity\Rule;
use Oro\Bundle\RuleBundle\Entity\RuleInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;

/**
 * Listens to Rule Entity save, delete events and invalidates cache
 */
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

    public function __construct(ShippingPriceCache $priceCache)
    {
        $this->priceCache = $priceCache;
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postPersist($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postUpdate($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    public function postRemove($entity, LifecycleEventArgs $args)
    {
        $this->invalidateCache($entity, $args);
    }

    /**
     * @param RuleInterface $rule
     * @param LifecycleEventArgs $args
     *
     * @return boolean
     */
    protected function isShippingRule(RuleInterface $rule, LifecycleEventArgs $args)
    {
        $repository = $args->getObjectManager()->getRepository(ShippingMethodsConfigsRule::class);
        if ($repository->findOneBy(['rule' => $rule])) {
            return true;
        }
        return false;
    }

    /**
     * @param RuleInterface|ShippingMethodsConfigsRule|ShippingMethodConfig|ShippingMethodTypeConfig $entity
     * @param LifecycleEventArgs $args
     */
    protected function invalidateCache($entity, LifecycleEventArgs $args)
    {
        if (!$this->executed) {
            if (!($entity instanceof Rule) || ($entity instanceof Rule && $this->isShippingRule($entity, $args))) {
                $this->priceCache->deleteAllPrices();
                $this->executed = true;
            }
        }
    }
}

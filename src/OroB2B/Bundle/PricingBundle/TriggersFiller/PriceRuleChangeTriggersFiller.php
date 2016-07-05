<?php

namespace OroB2B\Bundle\PricingBundle\TriggersFiller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleChangeTriggersFiller
{
    /**
     * @var Registry
     */
    protected $registry;
    
    /**
     * @param Registry $registry
     */
    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * @param PriceRule $priceRule
     * @param Product|null $product
     */
    public function createTrigger(PriceRule $priceRule, Product $product = null)
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManagerForClass('OroB2BPricingBundle:PriceRuleChangeTrigger');
        
        $priceRuleChangeTrigger = new PriceRuleChangeTrigger($priceRule, $product);
        
        $em->persist($priceRuleChangeTrigger);
        $em->flush($priceRuleChangeTrigger);
    }
}

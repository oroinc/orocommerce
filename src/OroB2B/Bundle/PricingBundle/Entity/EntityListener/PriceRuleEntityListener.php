<?php

namespace OroB2B\Bundle\PricingBundle\Entity\EntityListener;

use OroB2B\Bundle\PricingBundle\Entity\PriceRule;
use OroB2B\Bundle\PricingBundle\TriggersFiller\PriceRuleTriggerFiller;

class PriceRuleEntityListener
{
    /**
     * @var PriceRuleTriggerFiller
     */
    protected $priceRuleTriggersFiller;

    /**
     * @param PriceRuleTriggerFiller $priceRuleTriggersFiller
     */
    public function __construct(PriceRuleTriggerFiller $priceRuleTriggersFiller)
    {
        $this->priceRuleTriggersFiller = $priceRuleTriggersFiller;
    }

    /**
     * Recalculate price rules on price rule change.
     *
     * @param PriceRule $priceRule
     */
    public function preUpdate(PriceRule $priceRule)
    {
        $this->priceRuleTriggersFiller->addTriggersForPriceList($priceRule->getPriceList());
    }

    /**
     * Recalculate price rules on price rule remove.
     *
     * @param PriceRule $priceRule
     */
    public function preRemove(PriceRule $priceRule)
    {
        $this->priceRuleTriggersFiller->addTriggersForPriceList($priceRule->getPriceList());
    }
}

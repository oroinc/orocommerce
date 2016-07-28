<?php

namespace OroB2B\Bundle\PricingBundle\TriggersFiller;

use Oro\Bundle\B2BEntityBundle\Storage\ExtraActionEntityStorageInterface;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Entity\PriceRuleChangeTrigger;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class PriceRuleTriggerFiller
{
    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $extraActionsStorage;

    /**
     * @param ExtraActionEntityStorageInterface $extraActionsStorage
     */
    public function __construct(ExtraActionEntityStorageInterface $extraActionsStorage)
    {
        $this->extraActionsStorage = $extraActionsStorage;
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    public function addTriggersForPriceList(PriceList $priceList, Product $product = null)
    {
        if (!$this->isExistingTriggerWithPriseList($priceList)) {
            $trigger = new PriceRuleChangeTrigger($priceList, $product);
            $this->extraActionsStorage->scheduleForExtraInsert($trigger);
        }
    }

    /**
     * @param PriceList[] $priceLists
     * @param Product|null $product
     */
    public function addTriggersForPriceLists(array $priceLists, Product $product = null)
    {
        foreach ($priceLists as $priceList) {
            $this->addTriggersForPriceList($priceList, $product);
        }
    }

    /**
     * @param PriceList $priceList
     * @return bool
     */
    protected function isExistingTriggerWithPriseList(PriceList $priceList)
    {
        /** @var PriceRuleChangeTrigger[] $triggers */
        $triggers = $this->extraActionsStorage->getScheduledForInsert(PriceRuleChangeTrigger::class);
        foreach ($triggers as $trigger) {
            if ($trigger->getPriceList()->getId() === $priceList->getId()) {
                return true;
            }
        }

        return false;
    }
}

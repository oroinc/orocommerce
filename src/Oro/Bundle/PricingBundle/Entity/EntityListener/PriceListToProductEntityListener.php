<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\PricingBundle\Async\Topics;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\ProductBundle\Entity\Product;

class PriceListToProductEntityListener
{
    const FIELD_PRICE_LIST = 'priceList';
    const FIELD_PRODUCT = 'product';

    /**
     * @var PriceListTriggerHandler
     */
    protected $priceListTriggerHandler;

    /**
     * @var PriceRuleLexemeTriggerHandler
     */
    protected $priceRuleLexemeTriggerHandler;

    /**
     * @param PriceListTriggerHandler $priceListTriggerHandler
     * @param PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler
     */
    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler
    ) {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     */
    public function postPersist(PriceListToProduct $priceListToProduct)
    {
        $this->schedulePriceListRecalculations($priceListToProduct->getPriceList(), $priceListToProduct->getProduct());
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PriceListToProduct $priceListToProduct, PreUpdateEventArgs $event)
    {
        $this->recalculateForOldValues($priceListToProduct, $event);
        $this->schedulePriceListRecalculations($priceListToProduct->getPriceList(), $priceListToProduct->getProduct());
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     * @param LifecycleEventArgs $event
     */
    public function postRemove(PriceListToProduct $priceListToProduct, LifecycleEventArgs $event)
    {
        $this->schedulePriceListRecalculations($priceListToProduct->getPriceList(), $priceListToProduct->getProduct());

        $event->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->deleteByPriceList($priceListToProduct->getPriceList(), $priceListToProduct->getProduct());
    }

    /**
     * @param AssignmentBuilderBuildEvent $event
     */
    public function onAssignmentRuleBuilderBuild(AssignmentBuilderBuildEvent $event)
    {
        $this->schedulePriceListRecalculations($event->getPriceList(), $event->getProduct());
        $this->priceListTriggerHandler->sendScheduledTriggers();
    }

    /**
     * @param PriceList $priceList
     * @param Product|null $product
     */
    protected function scheduleDependentPriceListsUpdate(PriceList $priceList, Product $product = null)
    {
        $lexemes = $this->priceRuleLexemeTriggerHandler
            ->findEntityLexemes(PriceList::class, ['assignedProducts'], $priceList->getId());
        $this->priceRuleLexemeTriggerHandler->addTriggersByLexemes($lexemes, $product);
    }

    /**
     * @param PriceList $priceList
     * @param Product $product
     */
    protected function schedulePriceListRecalculations(PriceList $priceList, Product $product = null)
    {
        $this->priceListTriggerHandler->addTriggerForPriceList(Topics::RESOLVE_PRICE_RULES, $priceList, $product);
        $this->scheduleDependentPriceListsUpdate($priceList, $product);
    }

    /**
     * @param PriceListToProduct $priceListToProduct
     * @param PreUpdateEventArgs $event
     */
    protected function recalculateForOldValues(PriceListToProduct $priceListToProduct, PreUpdateEventArgs $event)
    {
        $oldProduct = $priceListToProduct->getProduct();
        $oldPriceList = $priceListToProduct->getPriceList();
        if ($event->hasChangedField(self::FIELD_PRICE_LIST)) {
            /** @var PriceList $oldPriceList */
            $oldPriceList = $event->getOldValue(self::FIELD_PRICE_LIST);
        }
        if ($event->hasChangedField(self::FIELD_PRODUCT)) {
            /** @var Product $oldProduct */
            $oldProduct = $event->getOldValue(self::FIELD_PRODUCT);
        }

        if ($event->hasChangedField(self::FIELD_PRICE_LIST) || $event->hasChangedField(self::FIELD_PRODUCT)) {
            $this->schedulePriceListRecalculations($oldPriceList, $oldProduct);
        }
    }
}

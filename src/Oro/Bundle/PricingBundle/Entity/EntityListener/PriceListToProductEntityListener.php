<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Async\Topic\ResolvePriceRulesTopic;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\AssignmentBuilderBuildEvent;
use Oro\Bundle\PricingBundle\Event\PriceListToProductSaveAfterEvent;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceRuleLexemeTriggerHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Handles adding, updating and removing PriceListToProduct entity.
 */
class PriceListToProductEntityListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    const FIELD_PRICE_LIST = 'priceList';
    const FIELD_PRODUCT = 'product';

    /** @var ShardManager */
    protected $shardManager;

    /** @var PriceListTriggerHandler */
    protected $priceListTriggerHandler;

    /** @var PriceRuleLexemeTriggerHandler */
    protected $priceRuleLexemeTriggerHandler;

    public function __construct(
        PriceListTriggerHandler $priceListTriggerHandler,
        PriceRuleLexemeTriggerHandler $priceRuleLexemeTriggerHandler,
        ShardManager $shardManager
    ) {
        $this->priceListTriggerHandler = $priceListTriggerHandler;
        $this->priceRuleLexemeTriggerHandler = $priceRuleLexemeTriggerHandler;
        $this->shardManager = $shardManager;
    }

    public function postPersist(PriceListToProduct $priceListToProduct)
    {
        $this->schedulePriceListRecalculations(
            $priceListToProduct->getPriceList(),
            [$priceListToProduct->getProduct()]
        );
    }

    public function onPriceListToProductSave(PriceListToProductSaveAfterEvent $event)
    {
        $this->postPersist($event->getPriceListToProduct());
    }

    public function preUpdate(PriceListToProduct $priceListToProduct, PreUpdateEventArgs $event)
    {
        $this->recalculateForOldValues($priceListToProduct, $event);
        $this->schedulePriceListRecalculations(
            $priceListToProduct->getPriceList(),
            [$priceListToProduct->getProduct()]
        );
    }

    public function postRemove(PriceListToProduct $priceListToProduct, LifecycleEventArgs $event)
    {
        $this->schedulePriceListRecalculations(
            $priceListToProduct->getPriceList(),
            [$priceListToProduct->getProduct()]
        );

        $event->getEntityManager()
            ->getRepository(ProductPrice::class)
            ->deleteByPriceList(
                $this->shardManager,
                $priceListToProduct->getPriceList(),
                [$priceListToProduct->getProduct()]
            );
    }

    public function onAssignmentRuleBuilderBuild(AssignmentBuilderBuildEvent $event)
    {
        $this->schedulePriceListRecalculations($event->getPriceList(), $event->getProducts());
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    protected function scheduleDependentPriceListsUpdate(PriceList $priceList, array $products = [])
    {
        if (!$this->enabled) {
            return;
        }

        $lexemes = $this->priceRuleLexemeTriggerHandler->findEntityLexemes(
            PriceList::class,
            ['assignedProducts'],
            $priceList->getId()
        );
        $this->priceRuleLexemeTriggerHandler->processLexemes($lexemes, $products);
    }

    /**
     * @param PriceList $priceList
     * @param array|Product[] $products
     */
    protected function schedulePriceListRecalculations(PriceList $priceList, array $products = [])
    {
        if (!$this->enabled) {
            return;
        }

        $this->priceListTriggerHandler->handlePriceListTopic(ResolvePriceRulesTopic::getName(), $priceList, $products);
        $this->scheduleDependentPriceListsUpdate($priceList, $products);
    }

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
            $this->schedulePriceListRecalculations($oldPriceList, [$oldProduct]);
        }
    }
}

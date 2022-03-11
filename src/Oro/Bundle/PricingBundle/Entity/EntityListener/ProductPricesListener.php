<?php

namespace Oro\Bundle\PricingBundle\Entity\EntityListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Event\ProductPricesUpdated;
use Oro\Bundle\PricingBundle\Handler\CombinedPriceListBuildTriggerHandler;
use Oro\Bundle\PricingBundle\Model\PriceListTriggerHandler;

/**
 * Based on the prices of all price lists included in the products, determines whether to initiate the rebuilding
 * combined price list.
 */
class ProductPricesListener implements OptionalListenerInterface, FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait, OptionalListenerTrait;

    private CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler;
    private PriceListTriggerHandler $priceListTriggerHandler;

    public function __construct(
        CombinedPriceListBuildTriggerHandler $combinedPriceListBuildTriggerHandler,
        PriceListTriggerHandler $priceListTriggerHandler
    ) {
        $this->combinedPriceListBuildTriggerHandler = $combinedPriceListBuildTriggerHandler;
        $this->priceListTriggerHandler = $priceListTriggerHandler;
    }

    public function onPricesUpdated(ProductPricesUpdated $event): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->isFeaturesEnabled()) {
            return; // Skip all recalculate actions.
        }

        $prices = array_merge($event->getSaved(), $event->getUpdated(), $event->getRemoved());
        $this->handle($prices);
    }

    private function handle(array $prices): void
    {
        $priceLists = array_reduce($prices, function (array $result, ProductPrice $productPrice) {
            $result[$productPrice->getPriceList()->getId()] = $productPrice->getPriceList();

            return $result;
        }, []);

        foreach ($priceLists as $priceList) {
            $this->combinedPriceListBuildTriggerHandler->handle($priceList);
        }
    }
}

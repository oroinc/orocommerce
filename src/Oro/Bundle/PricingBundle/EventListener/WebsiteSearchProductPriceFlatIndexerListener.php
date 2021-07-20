<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\AbstractPriceListTreeHandler;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\PriceListIdPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Adds flat pricing data to product search data.
 */
class WebsiteSearchProductPriceFlatIndexerListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public const MP_ALIAS = 'minimal_price_PRICE_LIST_ID_CURRENCY_UNIT';

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var AbstractPriceListTreeHandler
     */
    private $priceListTreeHandler;

    public function __construct(
        WebsiteContextManager $websiteContextManager,
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        AbstractPriceListTreeHandler $priceListTreeHandler
    ) {
        $this->websiteContextManager = $websiteContextManager;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->priceListTreeHandler = $priceListTreeHandler;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $website = $this->websiteContextManager->getWebsite($event->getContext());
        if (!$website) {
            $event->stopPropagation();

            return;
        }

        $repository = $this->getPriceRepository();
        /** @var PriceList $basePriceList */
        $basePriceList = $this->priceListTreeHandler->getPriceList(null, $website);
        $accuracy = $this->configManager->get('oro_pricing.price_indexation_accuracy');

        $prices = $repository->findMinByWebsiteForFilter(
            $website,
            $event->getEntities(),
            $basePriceList,
            $accuracy
        );

        foreach ($prices as $price) {
            $event->addPlaceholderField(
                $price['product_id'],
                self::MP_ALIAS,
                $price['value'],
                [
                    PriceListIdPlaceholder::NAME => $price['price_list_id'],
                    CurrencyPlaceholder::NAME => $price['currency'],
                    UnitPlaceholder::NAME => $price['unit'],
                ]
            );
        }

        $prices = $repository->findMinByWebsiteForSort(
            $website,
            $event->getEntities(),
            $basePriceList,
            $accuracy
        );
        foreach ($prices as $price) {
            $event->addPlaceholderField(
                $price['product_id'],
                'minimal_price_PRICE_LIST_ID_CURRENCY',
                $price['value'],
                [
                    PriceListIdPlaceholder::NAME => $price['price_list_id'],
                    CurrencyPlaceholder::NAME => $price['currency'],
                ]
            );
        }
    }

    private function getPriceRepository(): ProductPriceRepository
    {
        return $this->doctrine
            ->getManagerForClass(ProductPrice::class)
            ->getRepository(ProductPrice::class);
    }
}

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
use Oro\Bundle\SearchBundle\Formatter\ValueFormatterInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Adds flat pricing data to product search data.
 */
class WebsiteSearchProductPriceFlatIndexerListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;
    use ContextTrait;

    public const MP_ALIAS = 'minimal_price.PRICE_LIST_ID_CURRENCY_UNIT';
    public const MP_MERGED_ALIAS = 'minimal_price.PRICE_LIST_ID_CURRENCY';

    public function __construct(
        private WebsiteContextManager $websiteContextManager,
        private ManagerRegistry $doctrine,
        private ConfigManager $configManager,
        private AbstractPriceListTreeHandler $priceListTreeHandler,
        private ValueFormatterInterface $decimalValueFormatter
    ) {
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'pricing')) {
            return;
        }

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
                $this->decimalValueFormatter->format($price['value']),
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
                self::MP_MERGED_ALIAS,
                $this->decimalValueFormatter->format($price['value']),
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

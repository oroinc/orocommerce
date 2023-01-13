<?php

namespace Oro\Bundle\PricingBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;
use Oro\Bundle\PricingBundle\Placeholder\CPLIdPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\CurrencyPlaceholder;
use Oro\Bundle\PricingBundle\Placeholder\UnitPlaceholder;
use Oro\Bundle\SearchBundle\Formatter\ValueFormatterInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Adds placeholder fields for product fields
 */
class WebsiteSearchProductPriceIndexerListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;
    use ContextTrait;

    public const MP_ALIAS = 'minimal_price.CPL_ID_CURRENCY_UNIT';
    public const MP_MERGED_ALIAS = 'minimal_price.CPL_ID_CURRENCY';

    public function __construct(
        private WebsiteContextManager $websiteContextManager,
        private ManagerRegistry $doctrine,
        private ConfigManager $configManager,
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

        $websiteId = (int)$this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->doctrine->getRepository(CombinedProductPrice::class);
        $configCplId = $this->configManager->get(Configuration::getConfigKeyToPriceList());
        $configCpl = null;
        if ($configCplId) {
            $configCpl = $this->doctrine
                ->getManagerForClass(CombinedPriceList::class)
                ->getReference(CombinedPriceList::class, (int)$configCplId);
        }

        $prices = $repository->findMinByWebsiteForFilter(
            $websiteId,
            $event->getEntities(),
            $configCpl
        );

        foreach ($prices as $price) {
            $event->addPlaceholderField(
                $price['product'],
                self::MP_ALIAS,
                $this->decimalValueFormatter->format($price['value']),
                [
                    CPLIdPlaceholder::NAME => $price['cpl'],
                    CurrencyPlaceholder::NAME => $price['currency'],
                    UnitPlaceholder::NAME => $price['unit'],
                ]
            );
        }

        $prices = $repository->findMinByWebsiteForSort(
            $websiteId,
            $event->getEntities(),
            $configCpl
        );
        foreach ($prices as $price) {
            $event->addPlaceholderField(
                $price['product'],
                self::MP_MERGED_ALIAS,
                $this->decimalValueFormatter->format($price['value']),
                [
                    CPLIdPlaceholder::NAME => $price['cpl'],
                    CurrencyPlaceholder::NAME => $price['currency'],
                ]
            );
        }
    }
}

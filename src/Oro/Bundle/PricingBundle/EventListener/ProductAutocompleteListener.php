<?php
declare(strict_types = 1);

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;

/**
 * Adds minimal price value to the search autocomplete data.
 */
class ProductAutocompleteListener implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    private UserCurrencyManager $currencyManager;
    private NumberFormatter $numberFormatter;

    public function __construct(UserCurrencyManager $currencyManager, NumberFormatter $numberFormatter)
    {
        $this->currencyManager = $currencyManager;
        $this->numberFormatter = $numberFormatter;
    }

    public function onCollectAutocompleteFields(CollectAutocompleteFieldsEvent $event): void
    {
        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_flat')) {
            $event->addField('decimal.minimal_price.PRICE_LIST_ID_CURRENCY as pl_price');
        }

        if ($this->featureChecker->isFeatureEnabled('oro_price_lists_combined')) {
            $event->addField('decimal.minimal_price.CPL_ID_CURRENCY as cpl_price');
        }
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event): void
    {
        if (!$this->featureChecker->isFeatureEnabled('oro_price_lists_flat')
            && !$this->featureChecker->isFeatureEnabled('oro_price_lists_combined')) {
            return;
        }

        $currency = $this->currencyManager->getUserCurrency();

        $data = $event->getData();
        foreach ($data['products'] as $key => $productData) {
            $price = null;
            if (isset($productData['cpl_price']) && '' !== $productData['cpl_price']) {
                $price = $productData['cpl_price'];
            } elseif (isset($productData['pl_price']) && '' !== $productData['pl_price']) {
                $price = $productData['pl_price'];
            }
            unset($productData['cpl_price'], $productData['pl_price']);

            if (null !== $price) {
                $productData['price'] = $price;
                $productData['currency'] = $currency;
                $productData['formatted_price'] = $this->numberFormatter->formatCurrency($price, $currency);
            }

            $data['products'][$key] = $productData;
        }

        $event->setData($data);
    }
}

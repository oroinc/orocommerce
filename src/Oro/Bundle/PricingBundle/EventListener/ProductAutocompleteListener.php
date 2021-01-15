<?php
declare(strict_types = 1);

namespace Oro\Bundle\PricingBundle\EventListener;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Event\CollectAutocompleteFieldsEvent;
use Oro\Bundle\ProductBundle\Event\ProcessAutocompleteDataEvent;

/**
 * Adds minimal price value to the search autocomplete data.
 */
class ProductAutocompleteListener
{
    private UserCurrencyManager $currencyManager;
    private NumberFormatter $numberFormatter;

    public function __construct(UserCurrencyManager $currencyManager, NumberFormatter $numberFormatter)
    {
        $this->currencyManager = $currencyManager;
        $this->numberFormatter = $numberFormatter;
    }

    public function onCollectAutocompleteFields(CollectAutocompleteFieldsEvent $event): void
    {
        $event->addField('decimal.minimal_price_CPL_ID_CURRENCY as cpl_price');
        $event->addField('decimal.minimal_price_PRICE_LIST_ID_CURRENCY as pl_price');
    }

    public function onProcessAutocompleteData(ProcessAutocompleteDataEvent $event): void
    {
        $currency = $this->currencyManager->getUserCurrency();

        $data = $event->getData();
        foreach ($data as $sku => $productData) {
            $price = null;
            if (null !== $productData['cpl_price'] && '' !== $productData['cpl_price']) {
                $price = $productData['cpl_price'];
            } elseif (null !== $productData['pl_price'] && '' !== $productData['pl_price']) {
                $price = $productData['pl_price'];
            }
            unset($productData['cpl_price'], $productData['pl_price']);

            if (null !== $price) {
                $productData['price'] = $price;
                $productData['currency'] = $currency;
                $productData['formatted_price'] = $this->numberFormatter->formatCurrency($price, $currency);
            }

            $data[$sku] = $productData;
        }

        $event->setData($data);
    }
}

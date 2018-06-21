<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;

class ProductPriceProvider implements ProductPriceProviderInterface
{
    /**
     * @var ProductPriceStorageInterface
     */
    protected $priceStorage;

    /**
     * @var UserCurrencyManager
     */
    protected $currencyManager;

    /**
     * @param ProductPriceStorageInterface $priceStorage
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(ProductPriceStorageInterface $priceStorage, UserCurrencyManager $currencyManager)
    {
        $this->priceStorage = $priceStorage;
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        return array_intersect(
            $this->currencyManager->getAvailableCurrencies(),
            $this->priceStorage->getSupportedCurrencies($scopeCriteria)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesByScopeCriteriaAndProductIds(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productIds,
        $currency = null,
        $unitCode = null
    ) {
        $result = [];
        $currencies = null;
        if ($currency) {
            // TODO: BB-14587 CHECK THIS LOGIC!!! Currency may change here, >
            // TODO < if passed currency is not allowed then it will be replaced with user selected
            $currencies = $this->getAllowedCurrencies($scopeCriteria, [$currency]);
        }

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $prices = $this->priceStorage->getPrices($scopeCriteria, $productIds, $productUnitCodes, $currencies);

        if ($prices) {
            foreach ($prices as $price) {
                $result[$price['id']][] = [
                    'price' => $price['value'],
                    'currency' => $price['currency'],
                    'quantity' => $price['quantity'],
                    'unit' => $price['code']
                ];
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchedPrices(array $productPriceCriterias, ProductPriceScopeCriteriaInterface $scopeCriteria)
    {
        $productIds = [];
        $productUnitCodes = [];
        $currencies = [];
        $result = [];

        /** @var ProductPriceCriteria $productPriceCriteria */
        foreach ($productPriceCriterias as $productPriceCriteria) {
            $productIds[] = $productPriceCriteria->getProduct()->getId();
            $productUnitCodes[] = $productPriceCriteria->getProductUnit()->getCode();
            $currencies[] = $productPriceCriteria->getCurrency();
        }

        // TODO: BB-14587 CHECK THIS LOGIC!!! Currency may change here, >
        // TODO < if passed currencies are not allowed then them will be replaced with user selected one.
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);

        $prices = $this->priceStorage->getPrices($scopeCriteria, $productIds, $productUnitCodes, $currencies);
        foreach ($productPriceCriterias as $productPriceCriteria) {
            $id = $productPriceCriteria->getProduct()->getId();
            $code = $productPriceCriteria->getProductUnit()->getCode();
            $quantity = $productPriceCriteria->getQuantity();
            $currency = $productPriceCriteria->getCurrency();
            $precision = $productPriceCriteria->getProductUnit()->getDefaultPrecision();

            $productPrices = array_filter(
                $prices,
                function (array $price) use ($id, $code, $currency) {
                    return (int)$price['id'] === $id && $price['code'] === $code && $price['currency'] === $currency;
                }
            );

            list($price, $matchedQuantity) = $this->matchPriceByQuantity($productPrices, $quantity);
            if ($price !== null) {
                $result[$productPriceCriteria->getIdentifier()] = Price::create(
                    $this->recalculatePricePerUnit($price, $matchedQuantity, $precision),
                    $currency
                );
            } else {
                $result[$productPriceCriteria->getIdentifier()] = null;
            }
        }

        return $result;
    }

    /**
     * @param float $price
     * @param float $quantityPerAmount
     * @param int $precision
     * @return float
     */
    protected function recalculatePricePerUnit($price, $quantityPerAmount, $precision)
    {
        if ($precision > 0 && $quantityPerAmount !== 0.0) {
            return $price / $quantityPerAmount;
        }

        return $price;
    }

    /**
     * @param array $prices
     * @param float $expectedQuantity
     * @return array
     */
    protected function matchPriceByQuantity(array $prices, $expectedQuantity)
    {
        $price = null;
        $matchedQuantity = null;
        foreach ($prices as $productPrice) {
            $quantity = (float)$productPrice['quantity'];

            if ($expectedQuantity >= $quantity) {
                $price = (float)$productPrice['value'];
                $matchedQuantity = $quantity;
            }
        }

        return [$price, $matchedQuantity];
    }

    /**
     * Restrict currencies list to getSupportedCurrencies. If no supported pass User Currency
     *
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $currencies
     * @return array
     */
    protected function getAllowedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria, array $currencies): array
    {
        $currencies = array_intersect($currencies, $this->getSupportedCurrencies($scopeCriteria));
        if (!$currencies) {
            $currencies = [$this->currencyManager->getUserCurrency($scopeCriteria->getWebsite())];
        }

        return $currencies;
    }
}

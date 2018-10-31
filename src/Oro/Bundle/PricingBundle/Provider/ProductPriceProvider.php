<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;

/**
 * Read prices from storage and return requested prices to the client in expected format.
 */
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
    public function getSupportedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria): array
    {
        return array_intersect(
            $this->currencyManager->getAvailableCurrencies(),
            $this->priceStorage->getSupportedCurrencies($scopeCriteria)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getPricesByScopeCriteriaAndProducts(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $products,
        $currency = null,
        $unitCode = null
    ): array {
        $currencies = null;
        if ($currency) {
            // TODO: BB-14587 CHECK THIS LOGIC!!! Currency may change here, >
            // TODO < if passed currency is not allowed then it will be replaced with user selected
            $currencies = $this->getAllowedCurrencies($scopeCriteria, [$currency]);
        }

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $prices = $this->priceStorage->getPrices($scopeCriteria, $products, $productUnitCodes, $currencies);

        $result = [];
        foreach ($prices as $price) {
            $result[$price->getProduct()->getId()][] = $price;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchedPrices(
        array $productPriceCriterias,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        $products = [];
        $productUnitCodes = [];
        $currencies = [];
        $result = [];

        /** @var ProductPriceCriteria $productPriceCriteria */
        foreach ($productPriceCriterias as $productPriceCriteria) {
            $products[] = $productPriceCriteria->getProduct();
            $productUnitCodes[] = $productPriceCriteria->getProductUnit()->getCode();
            $currencies[] = $productPriceCriteria->getCurrency();
        }

        // TODO: BB-14587 CHECK THIS LOGIC!!! Currency may change here, >
        // TODO < if passed currencies are not allowed then them will be replaced with user selected one.
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);

        $prices = $this->priceStorage->getPrices($scopeCriteria, $products, $productUnitCodes, $currencies);
        foreach ($productPriceCriterias as $productPriceCriteria) {
            $id = $productPriceCriteria->getProduct()->getId();
            $code = $productPriceCriteria->getProductUnit()->getCode();
            $quantity = $productPriceCriteria->getQuantity();
            $currency = $productPriceCriteria->getCurrency();
            $precision = $productPriceCriteria->getProductUnit()->getDefaultPrecision();

            $productPrices = array_filter(
                $prices,
                function (ProductPriceInterface $priceData) use ($id, $code, $currency) {
                    return $priceData->getProduct()->getId() === $id
                        && $priceData->getUnit()->getCode() === $code
                        && $priceData->getPrice()->getCurrency() === $currency;
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
     * @param array|ProductPriceInterface[] $pricesData
     * @param float $expectedQuantity
     * @return array
     */
    protected function matchPriceByQuantity(array $pricesData, $expectedQuantity)
    {
        $price = null;
        $matchedQuantity = null;
        foreach ($pricesData as $priceData) {
            $quantity = $priceData->getQuantity();

            if ($expectedQuantity >= $quantity) {
                $price = $priceData->getPrice()->getValue();
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
            $currency = $this->currencyManager->getUserCurrency($scopeCriteria->getWebsite());
            if ($currency) {
                $currencies = [$currency];
            }
        }

        return $currencies;
    }
}

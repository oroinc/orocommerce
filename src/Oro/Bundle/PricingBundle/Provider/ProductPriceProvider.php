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
    public function __construct(
        ProductPriceStorageInterface $priceStorage,
        UserCurrencyManager $currencyManager
    ) {
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
        array $currencies,
        string $unitCode = null
    ):array {
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        if (empty($currencies)) {
            /**
             * There is no sense to get prices because of no allowed currencies present.
             */
            return [];
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
        array $productPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        $products = [];
        $productUnitCodes = [];
        $currencies = [];
        $result = [];

        /** @var ProductPriceCriteria $productPriceCriterion */
        foreach ($productPriceCriteria as $productPriceCriterion) {
            $products[] = $productPriceCriterion->getProduct();
            $productUnitCodes[] = $productPriceCriterion->getProductUnit()->getCode();
            $currencies[] = $productPriceCriterion->getCurrency();

            if (!\in_array($productPriceCriterion->getCurrency(), $currencies)) {
                $currencies[] = $productPriceCriterion->getCurrency();
            }
        }

        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);

        $prices = [];
        /**
         * There is no sense to get prices when no allowed currencies present.
         */
        if ($currencies) {
            $prices = $this->priceStorage->getPrices($scopeCriteria, $products, $productUnitCodes, $currencies);
        }

        foreach ($productPriceCriteria as $productPriceCriterion) {
            $id = $productPriceCriterion->getProduct()->getId();
            $code = $productPriceCriterion->getProductUnit()->getCode();
            $quantity = $productPriceCriterion->getQuantity();
            $currency = $productPriceCriterion->getCurrency();
            $precision = $productPriceCriterion->getProductUnit()->getDefaultPrecision();

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
                $result[$productPriceCriterion->getIdentifier()] = Price::create(
                    $this->recalculatePricePerUnit($price, $matchedQuantity, $precision),
                    $currency
                );
            } else {
                $result[$productPriceCriterion->getIdentifier()] = null;
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
     * Restrict currencies list to getSupportedCurrencies
     *
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $currencies
     * @return array
     */
    protected function getAllowedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria, array $currencies): array
    {
        if (empty($currencies)) {
            return $currencies;
        }

        $currencies = array_intersect($currencies, $this->getSupportedCurrencies($scopeCriteria));
        return $currencies;
    }
}

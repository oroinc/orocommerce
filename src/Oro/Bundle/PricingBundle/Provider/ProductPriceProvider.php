<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaDataExtractor\ProductPriceCriteriaDataExtractorInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\PriceByMatchingCriteria\ProductPriceByMatchingCriteriaProviderInterface;
use Oro\Bundle\PricingBundle\Storage\ProductPriceStorageInterface;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Read prices from storage and return requested prices to the client in expected format.
 */
class ProductPriceProvider implements ProductPriceProviderInterface, MatchedProductPriceProviderInterface
{
    protected ProductPriceStorageInterface $priceStorage;
    protected UserCurrencyManager $currencyManager;
    private ProductPriceCriteriaDataExtractorInterface $productPriceCriteriaDataExtractor;
    private ProductPriceByMatchingCriteriaProviderInterface $priceByMatchingCriteriaProvider;
    private MemoryCacheProviderInterface $memoryCacheProvider;

    public function __construct(
        ProductPriceStorageInterface $priceStorage,
        UserCurrencyManager $currencyManager,
        ProductPriceCriteriaDataExtractorInterface $productPriceCriteriaDataExtractor,
        ProductPriceByMatchingCriteriaProviderInterface $priceByMatchingCriteriaProvider,
        MemoryCacheProviderInterface $memoryCacheProvider
    ) {
        $this->priceStorage = $priceStorage;
        $this->currencyManager = $currencyManager;
        $this->productPriceCriteriaDataExtractor = $productPriceCriteriaDataExtractor;
        $this->priceByMatchingCriteriaProvider = $priceByMatchingCriteriaProvider;
        $this->memoryCacheProvider = $memoryCacheProvider;
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
    ): array {
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);
        if (empty($currencies)) {
            // There is no sense to get prices because of no allowed currencies present.
            return [];
        }

        $productsIds = [];
        foreach ($products as $product) {
            $productId = is_a($product, Product::class) ? $product->getId() : (int)$product;
            $productsIds[$productId] = $productId;
        }

        $productUnitCodes = $unitCode ? [$unitCode] : null;
        $prices = $this->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);

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
        /** @var array<string,?ProductPriceInterface> $matchingPricesByIdentifier */
        $matchingPricesByIdentifier = [];
        $matchingProductPrices = $this->getMatchingProductPricesGenerator($productPriceCriteria, $scopeCriteria);
        foreach ($matchingProductPrices as $identifier => $matchingProductPrice) {
            $matchingPricesByIdentifier[$identifier] = $matchingProductPrice?->getPrice();
        }

        return $matchingPricesByIdentifier;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchedProductPrices(
        array $productsPriceCriteria,
        ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
    ): array {
        /** @var array<string,?ProductPriceInterface> $matchingProductPricesByIdentifier */
        $matchingProductPricesByIdentifier = [];
        $matchingProductPrices = $this->getMatchingProductPricesGenerator(
            $productsPriceCriteria,
            $productPriceScopeCriteria
        );

        foreach ($matchingProductPrices as $identifier => $matchingProductPrice) {
            $matchingProductPricesByIdentifier[$identifier] = $matchingProductPrice;
        }

        return $matchingProductPricesByIdentifier;
    }

    /**
     * @param array<ProductPriceCriteria> $productsPriceCriteria
     * @param ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
     *
     * @return \Generator<string,ProductPriceInterface>
     */
    private function getMatchingProductPricesGenerator(
        array $productsPriceCriteria,
        ProductPriceScopeCriteriaInterface $productPriceScopeCriteria
    ): \Generator {
        [$productsIds, $unitCodes, $currencies] = $this->extractCriteriaData(
            $productsPriceCriteria,
            $productPriceScopeCriteria
        );

        $productPriceCollection = new ProductPriceCollectionDTO(
            $this->getPrices($productPriceScopeCriteria, $productsIds, $unitCodes, $currencies)
        );

        foreach ($productsPriceCriteria as $productPriceCriterion) {
            $identifier = $productPriceCriterion->getIdentifier();
            $productPrice = $this->priceByMatchingCriteriaProvider->getProductPriceMatchingCriteria(
                $productPriceCriterion,
                $productPriceCollection
            );
            yield $identifier => $productPrice;
        }
    }

    /**
     * @param ProductPriceInterface[] $prices
     */
    private function sortPrices(array &$prices): void
    {
        usort($prices, static function (ProductPriceDTO $a, ProductPriceDTO $b) {
            $codeA = $a->getUnit()->getCode();
            $codeB = $b->getUnit()->getCode();
            if ($codeA === $codeB) {
                return $a->getQuantity() <=> $b->getQuantity();
            }

            return $codeA <=> $codeB;
        });
    }

    /**
     * @param ProductPriceScopeCriteriaInterface $scopeCriteria
     * @param array $productsIds
     * @param array|null $productUnitCodes
     * @param array|null $currencies
     *
     * @return ProductPriceInterface[]
     */
    private function getPrices(
        ProductPriceScopeCriteriaInterface $scopeCriteria,
        array $productsIds,
        array $productUnitCodes = null,
        array $currencies = null
    ): array {
        if (!$currencies) {
            // There is no sense to get prices when no allowed currencies present.
            return [];
        }

        return (array)$this->memoryCacheProvider->get(
            [
                'product_price_scope_criteria' => $scopeCriteria,
                $productsIds,
                $currencies,
                $productUnitCodes,
            ],
            function () use ($scopeCriteria, $productsIds, $productUnitCodes, $currencies) {
                $prices = $this->priceStorage->getPrices($scopeCriteria, $productsIds, $productUnitCodes, $currencies);
                $this->sortPrices($prices);

                return $prices;
            }
        );
    }

    /**
     * Restrict currencies list to getSupportedCurrencies
     */
    protected function getAllowedCurrencies(ProductPriceScopeCriteriaInterface $scopeCriteria, array $currencies): array
    {
        if (empty($currencies)) {
            return $currencies;
        }

        return array_intersect($currencies, $this->getSupportedCurrencies($scopeCriteria));
    }

    private function extractCriteriaData(
        array $productsPriceCriteria,
        ProductPriceScopeCriteriaInterface $scopeCriteria
    ): array {
        $criteriaData = [];
        foreach ($productsPriceCriteria as $productPriceCriterion) {
            $criteriaData[] = $this->productPriceCriteriaDataExtractor->extractCriteriaData($productPriceCriterion);
        }
        $criteriaData = array_merge_recursive(...$criteriaData);

        $currencies = $criteriaData[ProductPriceCriteriaDataExtractorInterface::CURRENCIES] ?? [];
        $currencies = $this->getAllowedCurrencies($scopeCriteria, $currencies);

        return [
            array_unique($criteriaData[ProductPriceCriteriaDataExtractorInterface::PRODUCT_IDS] ?? []),
            array_unique($criteriaData[ProductPriceCriteriaDataExtractorInterface::UNIT_CODES] ?? []),
            array_unique($currencies),
        ];
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

/**
 * Provides methods to get prices with currencies, units and quantities
 * for regular products, configurable products and product variants
 */
class FrontendProductPricesProvider
{
    /**
     * @var array
     */
    protected $productPrices = [];

    /**
     * @var array
     */
    protected $variantsPrices = [];

    /**
     * @var ProductPriceScopeCriteriaRequestHandler
     */
    protected $scopeCriteriaRequestHandler;

    /**
     * @var ProductVariantAvailabilityProvider
     */
    protected $productVariantAvailabilityProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var ProductPriceFormatter
     */
    protected $productPriceFormatter;

    /**
     * @var ProductPriceProviderInterface
     */
    protected $productPriceProvider;

    public function __construct(
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceFormatter $productPriceFormatter,
        ProductPriceProviderInterface $productPriceProvider
    ) {
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->productPriceProvider = $productPriceProvider;
    }

    /**
     * @param Product $product
     *
     * @return ProductPrice[]
     */
    public function getByProduct(Product $product)
    {
        $this->prepareAndSetProductsPrices([$product]);

        return $this->getProductPrices($product->getId());
    }

    /**
     * @param Product $product
     *
     * @return ProductPrice[]
     */
    public function getVariantsPricesByProduct(Product $product)
    {
        $this->prepareAndSetProductsPrices([$product]);

        return $this->getVariantsPrices($product->getId());
    }

    /**
     * @param Product[] $products
     *
     * @return array
     */
    public function getByProducts($products)
    {
        $this->prepareAndSetProductsPrices($products);
        $productPrices = [];

        foreach ($products as $product) {
            $productId = $product->getId();
            if ($this->productPrices[$productId]) {
                $productPrices[$productId] = $this->getProductPrices($productId);
            }
        }

        return $productPrices;
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    protected function getProductPrices(int $productId)
    {
        return $this->productPrices[$productId] ?? [];
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    protected function getVariantsPrices(int $productId)
    {
        return $this->variantsPrices[$productId] ?? [];
    }

    /**
     * @param Product[] $products
     */
    protected function prepareAndSetProductsPrices(array $products)
    {
        $products = array_filter($products, function (Product $product) {
            return !array_key_exists($product->getId(), $this->productPrices);
        });

        if (!$products) {
            return;
        }

        $configurableProducts = $this->productVariantAvailabilityProvider
            ->getSimpleProductsGroupedByConfigurable($products);
        if ($configurableProducts) {
            $products = array_merge($products, array_merge(...array_values($configurableProducts)));
        }
        // Can't use array_unique here, because it uses __toString() for comparison which uses LocalizedFallbackValue
        // And array_unique with SORT_REGULAR option leads to nesting level error on complex objects
        $uniqueProducts = [];
        foreach ($products as $product) {
            $uniqueProducts[$product->getId()] = $product;
        }
        $products = array_values($uniqueProducts);

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria(),
            $products,
            [$this->userCurrencyManager->getUserCurrency()]
        );

        $this->setProductsPrices($products, $prices);

        foreach ($configurableProducts as $configurableId => $simpleProducts) {
            foreach ($simpleProducts as $product) {
                if ($this->productPrices[$product->getId()]) {
                    $this->variantsPrices[$configurableId][$product->getId()] = $this->productPrices[$product->getId()];
                }
            }
        }
    }

    /**
     * @param Product[] $products
     * @param array $prices
     */
    protected function setProductsPrices($products, $prices)
    {
        $productsPricesByUnit = [];
        foreach ($prices as $productId => $productPrices) {
            /** @var ProductPriceInterface $price */
            foreach ($productPrices as $price) {
                $productsPricesByUnit[$productId][$price->getUnit()->getCode()][] = $price;
            }
        }
        $formattedProductsPricesByUnit = $this->productPriceFormatter->formatProducts($productsPricesByUnit);

        foreach ($products as $product) {
            $unitPrecisions = $product->getUnitPrecisions();
            $unitsToSell = [];
            foreach ($unitPrecisions as $unitPrecision) {
                $unitsToSell[$unitPrecision->getUnit()->getCode()] = $unitPrecision->isSell();
            }

            $this->productPrices[$product->getId()] = array_filter(
                $formattedProductsPricesByUnit[$product->getId()] ?? [],
                function (array $formattedPriceData) use ($unitsToSell) {
                    return !empty($unitsToSell[$formattedPriceData['unit']]);
                }
            );
        }
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    protected function isProductHasPrices(Product $product)
    {
        $this->prepareAndSetProductsPrices([$product]);

        return !empty($this->productPrices[$product->getId()]);
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function isShowProductPriceContainer(Product $product)
    {
        return $product->getType() !== Product::TYPE_CONFIGURABLE || $this->isProductHasPrices($product);
    }
}

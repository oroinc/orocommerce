<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;

class FrontendProductPricesProvider
{
    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * @var array
     */
    protected $productPrices = [];

    /**
     * @var array
     */
    protected $variantsPrices = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var ProductVariantAvailabilityProvider
     */
    protected $productVariantAvailabilityProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param ProductVariantAvailabilityProvider $productVariantAvailabilityProvider
     * @param UserCurrencyManager $userCurrencyManager
     * @param ProductPriceFormatter $productPriceFormatter
     * @param ShardManager $shardManager
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler,
        ProductVariantAvailabilityProvider $productVariantAvailabilityProvider,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceFormatter $productPriceFormatter,
        ShardManager $shardManager
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->productVariantAvailabilityProvider = $productVariantAvailabilityProvider;
        $this->shardManager = $shardManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceFormatter = $productPriceFormatter;
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
    public function getVariantsPricesByProducts($products)
    {
        $this->prepareAndSetProductsPrices($products);
        $productPrices = [];

        foreach ($products as $product) {
            if ($product->getType() === Product::TYPE_CONFIGURABLE) {
                $productId = $product->getId();
                $productPrices[$productId] = $this->getVariantsPricesByProduct($product);
            }
        }

        return $productPrices;
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
        return array_key_exists($productId, $this->productPrices) ? $this->productPrices[$productId] : [];
    }

    /**
     * @param int $productId
     *
     * @return array
     */
    protected function getVariantsPrices(int $productId)
    {
        return array_key_exists($productId, $this->variantsPrices) ? $this->variantsPrices[$productId] : [];
    }

    /**
     * @param Product[] $products
     */
    protected function prepareAndSetProductsPrices($products)
    {
        $products = array_filter($products, function (Product $product) {
            return !array_key_exists($product->getId(), $this->productPrices);
        });

        if (!$products) {
            return;
        }

        $configurableProducts = [];
        foreach ($products as $product) {
            if ($product->isConfigurable()) {
                $configurableProducts[$product->getId()] = $this->productVariantAvailabilityProvider
                    ->getSimpleProductsByVariantFields($product);
                $products = array_merge($products, $configurableProducts[$product->getId()]);
            }
        }

        // Can't use array_unique here, because it uses __toString() for comparison which uses LocalizedFallbackValue
        // And array_unique with SORT_REGULAR option leads to nesting level error on complex objects
        $uniqueProducts = [];
        foreach ($products as $product) {
            $uniqueProducts[$product->getId()] = $product;
        }
        $products = array_values($uniqueProducts);

        $priceList = $this->priceListRequestHandler->getPriceListByCustomer();
        $productsIds = array_map(
            function (Product $product) {
                return $product->getId();
            },
            $products
        );

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroPricingBundle:CombinedProductPrice');
        $prices = $priceRepository->findByPriceListIdAndProductIds(
            $this->shardManager,
            $priceList->getId(),
            $productsIds,
            true,
            $this->userCurrencyManager->getUserCurrency(),
            null,
            [
                'unit' => 'ASC',
                'currency' => 'DESC',
                'quantity' => 'ASC',
            ]
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
     * @param ProductPrice[] $prices
     */
    protected function setProductsPrices($products, $prices)
    {
        $productsPrices = [];
        foreach ($prices as $price) {
            $productsPrices[$price->getProduct()->getId()][$price->getProductUnitCode()][] = [
                'quantity' => $price->getQuantity(),
                'price' => $price->getPrice()->getValue(),
                'currency' => $price->getPrice()->getCurrency(),
                'unit'  => $price->getUnit()->getCode(),
            ];
        }
        $productsPrices = $this->productPriceFormatter->formatProducts($productsPrices);

        foreach ($products as $product) {
            $unitPrecisions = $product->getUnitPrecisions();
            $unitsToSell = [];
            foreach ($unitPrecisions as $unitPrecision) {
                $unitsToSell[$unitPrecision->getUnit()->getCode()] = $unitPrecision->isSell();
            }

            $this->productPrices[$product->getId()] = array_filter(
                isset($productsPrices[$product->getId()]) ? $productsPrices[$product->getId()] : [],
                function ($price) use ($unitsToSell) {
                    return !empty($unitsToSell[$price['unit']]);
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

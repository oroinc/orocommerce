<?php

namespace Oro\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\ProductPriceRepository;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;

class FrontendProductPricesProvider
{
    /**
     * @var array
     */
    protected $productPrices = [];

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param PriceListRequestHandler $priceListRequestHandler
     * @param UserCurrencyManager $userCurrencyManager
     * @param ProductPriceFormatter $productPriceFormatter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        PriceListRequestHandler $priceListRequestHandler,
        UserCurrencyManager $userCurrencyManager,
        ProductPriceFormatter $productPriceFormatter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->priceListRequestHandler = $priceListRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->productPriceFormatter = $productPriceFormatter;
    }

    /**
     * @param Product $product
     * @return ProductPrice[]
     */
    public function getByProduct(Product $product)
    {
        if (!$product) {
            return null;
        }

        $this->setProductsPrices([$product]);

        return $this->productPrices[$product->getId()];
    }

    /**
     * @param Product[] $products
     * @return array
     */
    public function getByProducts($products)
    {
        $this->setProductsPrices($products);
        $productsUnits = [];

        foreach ($products as $product) {
            $productId = $product->getId();
            if ($this->productPrices[$productId]) {
                $productsUnits[$productId] = $this->productPrices[$productId];
            }
        }

        return $productsUnits;
    }

    /**
     * @param Product[] $products
     */
    protected function setProductsPrices($products)
    {
        $products = array_filter($products, function (Product $product) {
            return !array_key_exists($product->getId(), $this->productPrices);
        });
        if (!$products) {
            return;
        }

        $priceList = $this->priceListRequestHandler->getPriceListByAccount();
        $productsIds = array_map(function (Product $product) {
            return $product->getId();
        }, $products);

        /** @var ProductPriceRepository $priceRepository */
        $priceRepository = $this->doctrineHelper->getEntityRepository('OroPricingBundle:CombinedProductPrice');
        $prices = $priceRepository->findByPriceListIdAndProductIds(
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

        $productsPrices = [];
        foreach ($prices as $price) {
            $productsPrices[$price->getProduct()->getId()][$price->getProductUnitCode()][] = [
                'qty' => $price->getQuantity(),
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
}

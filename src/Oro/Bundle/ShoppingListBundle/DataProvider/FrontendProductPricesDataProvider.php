<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class FrontendProductPricesDataProvider
{
    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param UserCurrencyManager $userCurrencyManager
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        UserCurrencyManager $userCurrencyManager,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param ProductLineItemInterface[] $lineItems
     * @return array|null
     */
    public function getProductsMatchedPrice(array $lineItems)
    {
        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);

        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->priceListRequestHandler->getPriceListByCustomer()
        );

        $result = [];
        foreach ($prices as $key => $price) {
            $identifier = explode('-', $key);
            list($productId, $unitId) = $identifier;
            $result[$productId][$unitId] = $price;
        }

        return $result;
    }

    /**
     * @param array|ProductHolderInterface[] $lineItems
     * @return array
     */
    public function getProductsAllPrices(array $lineItems)
    {
        $prices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
            $this->priceListRequestHandler->getPriceListByCustomer()->getId(),
            array_map(function (ProductHolderInterface $lineItem) {
                return $lineItem->getProduct()->getId();
            }, $lineItems),
            $this->userCurrencyManager->getUserCurrency()
        );

        $pricesByUnit = [];
        foreach ($prices as $productId => $productPrices) {
            $pricesByUnit[$productId] = [];
            foreach ($productPrices as $productPrice) {
                $pricesByUnit[$productId][$productPrice['unit']][] = $productPrice;
            }
        }

        return $pricesByUnit;
    }

    /**
     * @param Collection|ProductLineItemInterface[] $lineItems
     * @return array
     */
    protected function getProductsPricesCriteria(array $lineItems)
    {
        $productsPricesCriteria = [];
        foreach ($lineItems as $lineItem) {
            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $this->userCurrencyManager->getUserCurrency()
            );
        }

        return $productsPricesCriteria;
    }
}

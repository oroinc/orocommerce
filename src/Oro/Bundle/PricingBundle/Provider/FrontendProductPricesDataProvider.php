<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

class FrontendProductPricesDataProvider
{
    /**
     * @var ProductPriceProviderInterface
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
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param UserCurrencyManager $userCurrencyManager
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        UserCurrencyManager $userCurrencyManager,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param ProductLineItemInterface[] $lineItems
     * @return array
     */
    public function getProductsMatchedPrice(array $lineItems)
    {
        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);

        $searchScope = new ProductPriceScopeCriteria();
        $searchScope->setCustomer($this->priceListRequestHandler->getCustomer());
        $searchScope->setWebsite($this->priceListRequestHandler->getWebsite());
        $prices = $this->productPriceProvider->getMatchedPrices($productsPriceCriteria, $searchScope);

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
        $searchScope = new ProductPriceScopeCriteria();
        $searchScope->setCustomer($this->priceListRequestHandler->getCustomer());
        $searchScope->setWebsite($this->priceListRequestHandler->getWebsite());
        $prices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
            $searchScope,
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

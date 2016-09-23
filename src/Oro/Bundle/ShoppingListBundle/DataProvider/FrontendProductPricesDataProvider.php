<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class FrontendProductPricesDataProvider
{
    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

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
     * @param SecurityFacade $securityFacade
     * @param UserCurrencyManager $userCurrencyManager
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        SecurityFacade $securityFacade,
        UserCurrencyManager $userCurrencyManager,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->securityFacade = $securityFacade;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param LineItem[] $lineItems
     * @return array|null
     */
    public function getProductsMatchedPrice(array $lineItems)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $productsPriceCriteria = $this->getProductsPricesCriteria($lineItems);

        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPriceCriteria,
            $this->priceListRequestHandler->getPriceListByAccount()
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
     * @param LineItem[] $lineItems
     * @return array|null
     */
    public function getProductsAllPrices(array $lineItems)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $prices = $this->productPriceProvider->getPriceByPriceListIdAndProductIds(
            $this->priceListRequestHandler->getPriceListByAccount()->getId(),
            array_map(function (LineItem $lineItem) {
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
     * @param Collection|LineItem[] $lineItems
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

<?php

namespace Oro\Bundle\RFPBundle\Provider;

use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceCollectionDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemProductPriceProviderInterface;
use Oro\Bundle\RFPBundle\Entity\Request;

/**
 * Provides tier product prices for the request product items of the specified request entity.
 */
class RequestProductLineItemTierPricesProvider
{
    private RequestProductPriceProvider $rfpProductPriceProvider;

    private ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider;

    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        RequestProductPriceProvider $rfpProductPriceProvider,
        ProductLineItemProductPriceProviderInterface $productLineItemProductPriceProvider,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->rfpProductPriceProvider = $rfpProductPriceProvider;
        $this->productLineItemProductPriceProvider = $productLineItemProductPriceProvider;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * @param Request $request
     * @param string|null $currency
     * @return array<int,array<int,ProductPriceInterface>>
     */
    public function getTierPrices(Request $request, ?string $currency = null): array
    {
        if ($currency === null) {
            $currency = $this->userCurrencyManager->getUserCurrency($request->getWebsite());
        }

        $productPricesByProduct = $this->rfpProductPriceProvider->getProductPrices($request);
        $productPriceCollection = new ProductPriceCollectionDTO(array_merge(...$productPricesByProduct));
        $tierPrices = [];

        foreach ($request->getRequestProducts() as $requestProductKey => $requestProduct) {
            $product = $requestProduct->getProduct();
            if ($product === null) {
                continue;
            }

            foreach ($requestProduct->getRequestProductItems() as $requestProductItemKey => $requestProductItem) {
                $productPrices = $this->productLineItemProductPriceProvider
                    ->getProductLineItemProductPrices($requestProductItem, $productPriceCollection, $currency);

                if (!empty($productPrices)) {
                    $tierPrices[$requestProductKey][$requestProductItemKey] = $productPrices;
                }
            }
        }

        return $tierPrices;
    }
}

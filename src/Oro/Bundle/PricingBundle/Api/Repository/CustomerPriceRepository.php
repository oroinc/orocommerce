<?php

namespace Oro\Bundle\PricingBundle\Api\Repository;

use Oro\Bundle\PricingBundle\Api\Model\CustomerPrice;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;

/**
 * The repository to get customer prices {@link ProductPrice} by scope criteria.
 */
class CustomerPriceRepository
{
    public function __construct(
        private ProductPriceProviderInterface $productPriceProvider
    ) {
    }

    public function getCustomerPrices(
        ProductPriceScopeCriteriaInterface $scope,
        array $productIds,
        array $currencies,
        ?string $unitCode
    ): array {
        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $scope,
            $productIds,
            $currencies,
            $unitCode
        );

        $customerPrices = [];
        foreach ($prices as $productId => $productPrices) {
            /** @var ProductPriceDTO $productPrice */
            foreach ($productPrices as $productPrice) {
                $customerPrices[] = new CustomerPrice(
                    null,
                    $productPrice->getPrice()->getCurrency(),
                    $productPrice->getQuantity(),
                    $productPrice->getPrice()->getValue(),
                    $productId,
                    $productPrice->getUnit()->getCode(),
                    $scope->getCustomer()?->getId(),
                    $scope->getWebsite()?->getId(),
                );
            }
        }

        return $customerPrices;
    }
}

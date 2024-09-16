<?php

namespace Oro\Bundle\PricingBundle\Api\Processor\CustomerPrice;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Api\Repository\CustomerPriceRepository;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads customer prices.
 */
class LoadCustomerPrices implements ProcessorInterface
{
    public function __construct(
        private CustomerPriceRepository $customerPricesRepository,
        private CurrencyProviderInterface $currencyProvider,
        private ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    /**
     * {@inheritdoc}
     * @param ContextInterface|ListContext $context
     */
    public function process(ContextInterface $context): void
    {
        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $filterValues = $context->getFilterValues();

        $scope = $this->getScope(
            $filterValues->getOne('customer')->getValue(),
            $filterValues->getOne('website')->getValue()
        );

        if (!$scope->getWebsite() || !$scope->getCustomer()) {
            $context->setResult([]);
            return;
        }

        $productIds = $this->getProductIds($filterValues->getOne('product')->getValue());
        $currencies = $this->getCurrencies($filterValues->getOne('currency')?->getValue());

        $customerPrices = $this->customerPricesRepository->getCustomerPrices(
            $scope,
            $productIds,
            $currencies,
            $filterValues->getOne('unit')?->getValue(),
        );

        $context->setResult($customerPrices);
    }

    private function getScope(int $customerId, int $websiteId): ProductPriceScopeCriteriaInterface
    {
        return $this->productPriceScopeCriteriaFactory->create(
            $this->doctrineHelper->getEntity(Website::class, $websiteId),
            $this->doctrineHelper->getEntity(Customer::class, $customerId)
        );
    }

    private function getProductIds(int|array $product): array
    {
        return \is_int($product) ? [$product] : $product;
    }

    private function getCurrencies(null|string|array $currency): array
    {
        if (!$currency) {
            $currencies = $this->currencyProvider->getCurrencyList();
        } else {
            $currencies = \is_string($currency) ? [$currency] : $currency;
        }

        return $currencies;
    }
}

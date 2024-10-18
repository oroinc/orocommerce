<?php

namespace Oro\Bundle\PricingBundle\Api\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Util\ComparisonExpressionsVisitor;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Api\Processor\ProductKitPrice\AddKitItemFilters;
use Oro\Bundle\PricingBundle\Api\ProductKitPriceMapper;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\PricingBundle\Provider\MatchedProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The repository to get product kit prices.
 */
class ProductKitPriceRepository
{
    private const int CUSTOMER_GUEST_FILTER_VALUE = 0;

    public function __construct(
        private UserCurrencyManager $currencyManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private CustomerUserRelationsProvider $customerUserRelationsProvider,
        private ProductPriceCriteriaFactoryInterface $productPriceCriteriaFactory,
        private MatchedProductPriceProviderInterface $matchedProductPriceProvider,
        private DoctrineHelper $doctrineHelper
    ) {
    }

    public function getProductKitPrices(?Criteria $criteria, array $filterValues): array
    {
        $filters = $this->getFilters($criteria, $filterValues);
        /** @var Customer|null $customer */
        $customer = $filters['customer'];
        /** @var Website|null $website */
        $website = $filters['website'];
        /** @var Product|null $product */
        $product = $filters['product'];

        if (null === $customer || null === $website || null === $product || null === $filters['unit']) {
            return [];
        }

        $scope = $this->productPriceScopeCriteriaFactory->create($website, $customer);
        if (!$this->isSupportedCurrency($scope, $filters['currency'])) {
            return [];
        }

        $orderLineItem = ProductKitPriceMapper::mapDataToOrderLineItem($filters);

        $productsPriceCriteria = $this->productPriceCriteriaFactory
            ->createListFromProductLineItems([$orderLineItem], $filters['currency']);

        if (!$productsPriceCriteria) {
            return [];
        }

        $matchedProductPrices = $this->matchedProductPriceProvider->getMatchedProductPrices(
            $productsPriceCriteria,
            $scope
        );

        return ProductKitPriceMapper::mapMatchedPricesToProductKitPrices($matchedProductPrices, $website, $customer);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getFilters(?Criteria $criteria, array $filterValues): array
    {
        if (null === $criteria) {
            throw new \InvalidArgumentException('The criteria is required.');
        }

        $visitor = new ComparisonExpressionsVisitor();
        $visitor->dispatch($criteria->getWhereExpression());
        $comparisons = $visitor->getComparisons();

        $filters = [];
        foreach ($comparisons as $comparison) {
            if ($comparison->getOperator() !== Comparison::EQ && $comparison->getOperator() !== Comparison::IN) {
                throw new \InvalidArgumentException(\sprintf(
                    'The "%s" operator is not supported for the "%s" filter.',
                    $comparison->getOperator(),
                    $comparison->getField()
                ));
            }
            $filters[$comparison->getField()] = $comparison->getValue()->getValue();
        }

        if (!\array_key_exists('customerId', $filters)) {
            throw new \InvalidArgumentException('The "customer" filter is required.');
        }
        if (!\array_key_exists('productId', $filters)) {
            throw new \InvalidArgumentException('The "product" filter is required.');
        }
        if (!\array_key_exists('websiteId', $filters)) {
            throw new \InvalidArgumentException('The "website" filter is required.');
        }
        if (!\array_key_exists('unit', $filters)) {
            throw new \InvalidArgumentException('The "unit" filter is required.');
        }
        if (!\array_key_exists('quantity', $filters)) {
            throw new \InvalidArgumentException('The "quantity" filter is required.');
        }

        $product = $this->getProduct($filters['productId']);

        $filters['customer'] = $this->getCustomer($filters['customerId']);
        $filters['website'] = $this->getWebsite($filters['websiteId']);
        $filters['product'] = $product;
        $filters['currency'] = $this->getCurrency($filters['currency'] ?? null);
        $filters['unit'] = $this->getProductUnit($filters['unit']);
        $filters['kitItems'] = $product ? $this->getKitItemFilters($filterValues, $product) : [];

        unset(
            $filters['customerId'],
            $filters['websiteId'],
            $filters['productId'],
        );

        return $filters;
    }

    /**
     * @return array[
     *    'kitItemId' => ['kitItem' => ProductKitItem, 'product' => Product, 'quantity' => 1],
     *    'kitItemId' => [...]
     *  ]
     */
    private function getKitItemFilters(array $filterValues, Product $product): array
    {
        $kitItemFilters = [];
        foreach ($filterValues as $filterKey => $filter) {
            $filter = \reset($filter);
            if (!$filter || !AddKitItemFilters::isKitItemFilter($filterKey)) {
                continue;
            }

            [, $kitItemId, $fieldKey] = \explode('.', $filter->getPath());

            if ($fieldKey === 'product') {
                $kitItem = $this->getKitItemById($product, $kitItemId);
                $kitItemProduct = $kitItem ? $this->getKitItemProductById($kitItem, $filter->getValue()) : null;

                $kitItemFilters[$kitItemId]['kitItem'] = $kitItem;
                $kitItemFilters[$kitItemId]['product'] = $kitItemProduct;
            } elseif ($fieldKey === 'quantity') {
                $kitItemFilters[$kitItemId]['quantity'] = $filter->getValue();
            }
        }

        return $kitItemFilters;
    }

    private function isSupportedCurrency(ProductPriceScopeCriteriaInterface $scope, string $currency): bool
    {
        $supportedCurrencies = $this->matchedProductPriceProvider->getSupportedCurrencies($scope);

        return \in_array($currency, $supportedCurrencies, true);
    }

    private function getKitItemProductById(ProductKitItem $kitItem, int $productId): ?Product
    {
        $result = $kitItem->getProducts()
            ->filter(static fn (Product $product) => $product->getId() === $productId)
            ->current();

        return $result !== false ? $result : null;
    }

    private function getKitItemById(Product $product, int $kitItemId): ?ProductKitItem
    {
        $result = $product->getKitItems()
            ->filter(static fn (ProductKitItem $kitItem) => $kitItem->getId() === $kitItemId)
            ->current();

        return $result !== false ? $result : null;
    }

    private function getCustomer(int $customerId): ?Customer
    {
        if (self::CUSTOMER_GUEST_FILTER_VALUE === $customerId) {
            return $this->customerUserRelationsProvider->getCustomerIncludingEmpty();
        }

        $customer = $this->doctrineHelper->getEntity(Customer::class, $customerId);
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $customer)) {
            return null;
        }

        return $customer;
    }

    private function getWebsite(int $websiteId): ?Website
    {
        $website = $this->doctrineHelper->getEntity(Website::class, $websiteId);
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $website)) {
            return null;
        }

        return $website;
    }

    private function getProduct(int $productId): ?Product
    {
        $product = $this->doctrineHelper->getEntity(Product::class, $productId);
        if (!$this->authorizationChecker->isGranted(BasicPermission::VIEW, $product)) {
            return null;
        }

        return $product;
    }

    private function getProductUnit(string $unit): ?ProductUnit
    {
        return $this->doctrineHelper->getEntity(ProductUnit::class, $unit);
    }

    private function getCurrency(?string $currency): string
    {
        if (!$currency) {
            $currency = $this->currencyManager->getUserCurrency() ?: $this->currencyManager->getDefaultCurrency();
        }

        return $currency;
    }
}

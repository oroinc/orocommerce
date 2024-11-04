<?php

namespace Oro\Bundle\PricingBundle\Api\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ComparisonExpressionsVisitor;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\QueryAclHelper;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Api\Model\CustomerPrice;
use Oro\Bundle\PricingBundle\Model\DTO\ProductPriceDTO;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\EntitySerializer\EntityConfig;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The repository to get customer prices.
 */
class CustomerPriceRepository
{
    private const int CUSTOMER_GUEST_FILTER_VALUE = 0;

    public function __construct(
        private ProductPriceProviderInterface $productPriceProvider,
        private CurrencyProviderInterface $currencyProvider,
        private ProductPriceScopeCriteriaFactoryInterface $productPriceScopeCriteriaFactory,
        private DoctrineHelper $doctrineHelper,
        private AuthorizationCheckerInterface $authorizationChecker,
        private QueryAclHelper $queryAclHelper,
        private CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
    }

    public function getCustomerPrices(?Criteria $criteria, RequestType $requestType): array
    {
        $filters = $this->getFilters($criteria, $requestType);
        /** @var Customer|null $customer */
        $customer = $filters['customer'];
        /** @var Website|null $website */
        $website = $filters['website'];
        $productIds = $filters['productIds'];
        if (null === $customer || null === $website || !$productIds) {
            return [];
        }

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProducts(
            $this->productPriceScopeCriteriaFactory->create($website, $customer),
            $productIds,
            $filters['currencies'],
            $filters['unit'] ?? null
        );

        $customerPrices = [];
        foreach ($prices as $productId => $productPrices) {
            /** @var ProductPriceDTO $productPrice */
            foreach ($productPrices as $productPrice) {
                $customerPrices[] = new CustomerPrice(
                    $customer->getId(),
                    $website->getId(),
                    $productId,
                    $productPrice->getPrice()->getCurrency(),
                    $productPrice->getQuantity(),
                    $productPrice->getPrice()->getValue(),
                    $productPrice->getUnit()->getCode()
                );
            }
        }

        return $customerPrices;
    }

    private function getFilters(?Criteria $criteria, RequestType $requestType): array
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

        $filters['customer'] = $this->getCustomer($filters['customerId']);
        $filters['website'] = $this->getWebsite($filters['websiteId']);
        $filters['productIds'] = $this->getProductIds((array)$filters['productId'], $requestType);
        $filters['currencies'] = $this->getCurrencies(
            \array_key_exists('currency', $filters) ? (array)$filters['currency'] : null
        );

        unset(
            $filters['customerId'],
            $filters['websiteId'],
            $filters['productId'],
            $filters['currency']
        );

        return $filters;
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

    private function getProductIds(array $productIds, RequestType $requestType): array
    {
        $qb = $this->doctrineHelper->createQueryBuilder(Product::class, 'p')
            ->select('p.id')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', $productIds);
        $rows = $this->queryAclHelper->protectQuery($qb, new EntityConfig(), $requestType)
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    private function getCurrencies(?array $currencies): array
    {
        return $currencies ?? $this->currencyProvider->getCurrencyList();
    }
}

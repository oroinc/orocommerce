<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\ProductVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityFallbackCategoryData;

abstract class CategoryCacheTestCase extends WebTestCase
{
    use LocalizationQueryTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductVisibilityFallbackCategoryData::class]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    protected function assertProductVisibilityResolvedCorrect(array $expectedData)
    {
        $this->assertEquals($expectedData, $this->fetchVisibility());
    }

    protected function getHiddenProducts(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(ProductVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('pvr');
        $this->selectHiddenProductSku($queryBuilder, 'pvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_map(function ($row) {
            return $row['sku'];
        }, $results);
    }

    protected function getHiddenCategories(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(CategoryVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('cvr');
        $this->selectHiddenCategoryTitles($queryBuilder, 'cvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_map(function ($row) {
            return $row['title'];
        }, $results);
    }

    protected function getHiddenProductsByCustomerGroups(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroupProductVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('agpvr')
            ->select('customerGroup.name as customer_group_name')
            ->join('agpvr.scope', 'scope')
            ->join('scope.customerGroup', 'customerGroup')
            ->orderBy('customerGroup.name');
        $this->selectHiddenProductSku($queryBuilder, 'agpvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['customer_group_name']])) {
                $results[$row['customer_group_name']] = [];
            }
            $results[$row['customer_group_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    protected function getHiddenCategoriesByCustomerGroups(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerGroupCategoryVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('agcvr')
            ->select('customerGroup.name as customer_group_name')
            ->addSelect('customerGroup.id')
            ->addSelect('agcvr.visibility')
            ->join('agcvr.scope', 'scope')
            ->join('scope.customerGroup', 'customerGroup')
            ->orderBy('customerGroup.name');
        $this->selectHiddenCategoryTitles($queryBuilder, 'agcvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['customer_group_name']])) {
                $results[$row['customer_group_name']] = [];
            }
            $results[$row['customer_group_name']][] = $row['title'];
            return $results;
        }, []);
    }

    protected function getHiddenProductsByCustomers(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerProductVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('apvr')
            ->select('customer.name as customer_name')
            ->join('apvr.scope', 'scope')
            ->join('scope.customer', 'customer')
            ->orderBy('customer.name');
        $this->selectHiddenProductSku($queryBuilder, 'apvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['customer_name']])) {
                $results[$row['customer_name']] = [];
            }
            $results[$row['customer_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    protected function getHiddenCategoriesByCustomers(): array
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getRepository(CustomerCategoryVisibilityResolved::class);
        $queryBuilder = $repository->createQueryBuilder('acvr')
            ->select('customer.name as customer_name')
            ->join('acvr.scope', 'scope')
            ->join('scope.customer', 'customer')
            ->orderBy('customer.name');
        $this->selectHiddenCategoryTitles($queryBuilder, 'acvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['customer_name']])) {
                $results[$row['customer_name']] = [];
            }
            $results[$row['customer_name']][] = $row['title'];
            return $results;
        }, []);
    }

    protected function selectHiddenProductSku(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->addSelect('product.sku')
            ->join($alias . '.product', 'product')
            ->andWhere($queryBuilder->expr()->eq(
                $alias . '.visibility',
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->addOrderBy($alias . '.category')
            ->addOrderBy('product.sku');
    }

    protected function selectHiddenCategoryTitles(QueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder
            ->join($alias . '.category', 'category')
            ->addSelect('category.id as catId')
            ->andWhere($queryBuilder->expr()->eq(
                $alias . '.visibility',
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->addOrderBy('title');

        $this->joinDefaultLocalizedValue($queryBuilder, 'category.titles', 'categoryTitles', 'title');
    }

    protected function fetchVisibility(): array
    {
        return [
            'hiddenCategories' => $this->getHiddenCategories(),
            'hiddenCategoriesByCustomerGroups' => $this->getHiddenCategoriesByCustomerGroups(),
            'hiddenCategoriesByCustomers' => $this->getHiddenCategoriesByCustomers(),
            'hiddenProducts' => $this->getHiddenProducts(),
            'hiddenProductsByCustomerGroups' => $this->getHiddenProductsByCustomerGroups(),
            'hiddenProductsByCustomers' => $this->getHiddenProductsByCustomers(),
        ];
    }
}

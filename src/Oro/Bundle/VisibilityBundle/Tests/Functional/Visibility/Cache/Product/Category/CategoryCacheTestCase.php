<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityFallbackCategoryData;

abstract class CategoryCacheTestCase extends WebTestCase
{
    use LocalizationQueryTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadProductVisibilityFallbackCategoryData::class
        ]);
        $this->getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();
    }

    protected function assertProductVisibilityResolvedCorrect(array $expectedData)
    {
        $this->assertEquals($expectedData, $this->fetchVisibility());
    }

    /**
     * @return array
     */
    protected function getHiddenProducts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\ProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('pvr');
        $this->selectHiddenProductSku($queryBuilder, 'pvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_map(function ($row) {
            return $row['sku'];
        }, $results);
    }

    /**
     * @return array
     */
    protected function getHiddenCategories()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CategoryVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('cvr');
        $this->selectHiddenCategoryTitles($queryBuilder, 'cvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_map(function ($row) {
            return $row['title'];
        }, $results);
    }

    /**
     * @return array
     */
    protected function getHiddenProductsByCustomerGroups()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupProductVisibilityResolved');
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

    /**
     * @return array
     */
    protected function getHiddenCategoriesByCustomerGroups()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
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

    /**
     * @return array
     */
    protected function getHiddenProductsByCustomers()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerProductVisibilityResolved');
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

    /**
     * @return array
     */
    protected function getHiddenCategoriesByCustomers()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');
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

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     */
    protected function selectHiddenProductSku(QueryBuilder $queryBuilder, $alias)
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

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $alias
     */
    protected function selectHiddenCategoryTitles(QueryBuilder $queryBuilder, $alias)
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

    /**
     * @return array
     */
    protected function fetchVisibility()
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

<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\LocaleBundle\Helper\LocalizationQueryTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\AccountBundle\Entity\VisibilityResolved\BaseVisibilityResolved;

abstract class CategoryCacheTestCase extends WebTestCase
{
    use LocalizationQueryTrait;

    public function setUp()
    {
        $this->initClient();

        $this->loadFixtures([
            'Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityFallbackCategoryData'
        ]);
    }

    /**
     * @param array $expectedData
     */
    protected function assertProductVisibilityResolvedCorrect(array $expectedData)
    {
        $this->assertEquals($expectedData, [
            'hiddenCategories' => $this->getHiddenCategories(),
            'hiddenCategoriesByAccountGroups' => $this->getHiddenCategoriesByAccountGroups(),
            'hiddenCategoriesByAccounts' => $this->getHiddenCategoriesByAccounts(),
        ]);
    }

    /**
     * @return array
     */
    protected function getHiddenProducts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved');
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
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\CategoryVisibilityResolved');
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
    protected function getHiddenProductsByAccountGroups()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('agpvr')
            ->select('accountGroup.name as account_group_name')
            ->join('agpvr.accountGroup', 'accountGroup')
            ->orderBy('accountGroup.name');
        $this->selectHiddenProductSku($queryBuilder, 'agpvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_group_name']])) {
                $results[$row['account_group_name']] = [];
            }
            $results[$row['account_group_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    /**
     * @return array
     */
    protected function getHiddenCategoriesByAccountGroups()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountGroupCategoryVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('agcvr')
            ->select('accountGroup.name as account_group_name')
            ->join('agcvr.accountGroup', 'accountGroup')
            ->orderBy('accountGroup.name');
        $this->selectHiddenCategoryTitles($queryBuilder, 'agcvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_group_name']])) {
                $results[$row['account_group_name']] = [];
            }
            $results[$row['account_group_name']][] = $row['title'];
            return $results;
        }, []);
    }

    /**
     * @return array
     */
    protected function getHiddenProductsByAccounts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountProductVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountProductVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('apvr')
            ->select('account.name as account_name')
            ->join('apvr.account', 'account')
            ->orderBy('account.name');
        $this->selectHiddenProductSku($queryBuilder, 'apvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_name']])) {
                $results[$row['account_name']] = [];
            }
            $results[$row['account_name']][] = $row['sku'];
            return $results;
        }, []);
    }

    /**
     * @return array
     */
    protected function getHiddenCategoriesByAccounts()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved')
            ->getRepository('OroAccountBundle:VisibilityResolved\AccountCategoryVisibilityResolved');
        $queryBuilder = $repository->createQueryBuilder('acvr')
            ->select('account.name as account_name')
            ->join('acvr.account', 'account')
            ->orderBy('account.name');
        $this->selectHiddenCategoryTitles($queryBuilder, 'acvr');
        $results = $queryBuilder->getQuery()
            ->getScalarResult();

        return array_reduce($results, function ($results, $row) {
            if (!isset($results[$row['account_name']])) {
                $results[$row['account_name']] = [];
            }
            $results[$row['account_name']][] = $row['title'];
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
            ->andWhere($queryBuilder->expr()->eq(
                $alias . '.visibility',
                BaseVisibilityResolved::VISIBILITY_HIDDEN
            ))
            ->addOrderBy('title');

        $this->joinDefaultLocalizedValue($queryBuilder, 'category.titles', 'categoryTitles', 'title');
    }
}

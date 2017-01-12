<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CustomerGroupCategoryResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeGroupSubtreeCacheBuilder;

/**
 * @dbIsolation
 */
class CustomerGroupCategoryResolvedCacheBuilderTest extends AbstractProductResolvedCacheBuilderTest
{
    /** @var Category */
    protected $category;

    /** @var CustomerGroup */
    protected $customerGroup;

    /** @var CustomerGroupCategoryResolvedCacheBuilder */
    protected $builder;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    /**
     * @var InsertFromSelectQueryExecutor
     */
    protected $insertExecutor;

    /**
     * @var Scope
     */
    protected $scope;

    protected function setUp()
    {
        parent::setUp();
        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->customerGroup = $this->getReference('customer_group.group3');

        $container = $this->client->getContainer();

        $indexScheduler = new ProductIndexScheduler(
            $container->get('oro_entity.doctrine_helper'),
            $container->get('event_dispatcher')
        );

        $this->insertExecutor = $container->get('oro_entity.orm.insert_from_select_query_executor');
        $this->scopeManager = $container->get('oro_scope.scope_manager');
        $this->scope = $this->scopeManager->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $this->customerGroup]
        );
        $this->builder = new CustomerGroupCategoryResolvedCacheBuilder(
            $container->get('doctrine'),
            $this->scopeManager,
            $indexScheduler,
            $this->insertExecutor
        );
        $this->builder->setCacheClass(
            $container->getParameter('oro_visibility.entity.customer_group_category_visibility_resolved.class')
        );
        $this->builder->setRepository(
            $container->get('oro_visibility.category_repository')
        );
        $this->builder->setCustomerGroupCategoryVisibilityRepository(
            $container->get('oro_visibility.customer_group_category_repository')
        );

        $subtreeBuilder = new VisibilityChangeGroupSubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_visibility.visibility.resolver.category_visibility_resolver'),
            $container->get('oro_config.manager'),
            $container->get('oro_scope.scope_manager')
        );

        $this->builder->setVisibilityChangeCustomerSubtreeCacheBuilder($subtreeBuilder);
    }

    public function testChangeCustomerGroupCategoryVisibilityToHidden()
    {
        $visibility = new CustomerGroupCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');
        $em->persist($visibility);
        $em->flush();
        $this->builder->buildCache();
        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeCustomerGroupCategoryVisibilityToHidden
     */
    public function testChangeCustomerGroupCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');
        $em->flush();
        $this->builder->buildCache();
        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeCustomerGroupCategoryVisibilityToHidden
     */
    public function testChangeCustomerGroupCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CustomerGroupCategoryVisibility::PARENT_CATEGORY);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');
        $em->flush();
        $this->builder->buildCache();
        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals(
            $visibility->getVisibility(),
            $visibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
            $visibilityResolved['visibility']
        );
    }

    /**
     * @depends testChangeCustomerGroupCategoryVisibilityToParentCategory
     */
    public function testChangeCustomerGroupCategoryVisibilityToAll()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CustomerGroupCategoryVisibility::CATEGORY);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility');
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertNull($visibilityResolved);
    }

    /**
     * @return array
     */
    protected function getVisibilityResolved()
    {
        /** @var EntityManager $em */
        $em = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved');
        $qb = $em->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->createQueryBuilder('customerCategoryVisibilityResolved');
        $entity = $qb->select('customerCategoryVisibilityResolved', 'customerCategoryVisibility')
            ->leftJoin('customerCategoryVisibilityResolved.sourceCategoryVisibility', 'customerCategoryVisibility')
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('customerCategoryVisibilityResolved.category', ':category'),
                    $qb->expr()->eq('customerCategoryVisibilityResolved.scope', ':scope')
                )
            )
            ->setParameters([
                'category' => $this->category,
                'scope' => $this->scope,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);

        return $entity;
    }

    /**
     * @return null|CustomerGroupCategoryVisibility
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerGroupCategoryVisibility')
            ->findOneBy(['category' => $this->category, 'scope' => $this->scope]);
    }

    /**
     * @param array $categoryVisibilityResolved
     * @param VisibilityInterface $categoryVisibility
     * @param integer $expectedVisibility
     */
    protected function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        $expectedVisibility
    ) {
        $this->assertNotNull($categoryVisibilityResolved);
        $this->assertEquals($this->category->getId(), $categoryVisibilityResolved['category_id']);
        $this->assertEquals($this->scope->getId(), $categoryVisibilityResolved['scope_id']);
        $this->assertEquals(
            CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
            $categoryVisibilityResolved['source']
        );
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }

    /**
     * @dataProvider buildCacheDataProvider
     * @param array $expectedVisibilities
     */
    public function testBuildCache(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->replaceReferencesWithIds($expectedVisibilities);
        usort($expectedVisibilities, [$this, 'sortByCategoryAndCustomerGroup']);

        $this->builder->buildCache();

        $actualVisibilities = $this->getResolvedVisibilities();
        usort($actualVisibilities, [$this, 'sortByCategoryAndCustomerGroup']);

        $this->assertEquals($expectedVisibilities, $actualVisibilities);
    }

    /**
     * @return array
     */
    public function buildCacheDataProvider()
    {
        return [
            [
                'expectedVisibilities' => [
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group2',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'customerGroup' => 'customer_group.group2',
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group3',
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'customerGroup' => 'customer_group.group3',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group3',
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group3',
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group3',
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group3',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByCategoryAndCustomerGroup(array $a, array $b)
    {
        if ($a['category'] == $b['category']) {
            return $a['customerGroup'] > $b['customerGroup'] ? 1 : -1;
        }

        return $a['category'] > $b['category'] ? 1 : -1;
    }

    /**
     * @param array $visibilities
     * @return array
     */
    protected function replaceReferencesWithIds(array $visibilities)
    {
        $rootCategory = $this->getRootCategory();
        foreach ($visibilities as $key => $row) {
            $category = $row['category'];
            /** @var Category $category */
            if ($category === self::ROOT) {
                $category = $rootCategory;
            } else {
                $category = $this->getReference($category);
            }

            $visibilities[$key]['category'] = $category->getId();

            /** @var CustomerGroup $category */
            $customerGroup = $this->getReference($row['customerGroup']);
            $visibilities[$key]['customerGroup'] = $customerGroup->getId();
        }
        return $visibilities;
    }

    /**
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        return $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerGroupCategoryVisibilityResolved')
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'IDENTITY(scope.customerGroup) as customerGroup',
                'entity.visibility',
                'entity.source'
            )
            ->join('entity.scope', 'scope')
            ->getQuery()
            ->getArrayResult();
    }
}

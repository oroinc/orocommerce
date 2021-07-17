<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Manager\ProductIndexScheduler;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ProductBundle\Search\Reindex\ProductReindexManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CustomerCategoryResolvedCacheBuilder;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\Subtree\VisibilityChangeCustomerSubtreeCacheBuilder;

class CustomerCategoryResolvedCacheBuilderTest extends AbstractProductResolvedCacheBuilderTest
{
    use ConfigManagerAwareTestTrait;

    /** @var Category */
    protected $category;

    /** @var Customer */
    protected $customer;

    /** @var CustomerCategoryResolvedCacheBuilder */
    protected $builder;

    /**
     * @var ScopeManager
     */
    protected $scopeManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $this->customer = $this->getReference('customer.level_1');

        $container = $this->client->getContainer();
        $this->scopeManager = $container->get('oro_scope.scope_manager');
        $this->scope = $this->scopeManager->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $this->customer]
        );

        $productReindexManager = new ProductReindexManager(
            $container->get('event_dispatcher')
        );

        $indexScheduler = new ProductIndexScheduler(
            $container->get('oro_entity.doctrine_helper'),
            $productReindexManager
        );

        $this->builder = new CustomerCategoryResolvedCacheBuilder(
            $container->get('doctrine'),
            $this->scopeManager,
            $indexScheduler,
            $container->get('oro_entity.orm.insert_from_select_query_executor'),
            $productReindexManager
        );
        $this->builder->setCacheClass(CustomerCategoryVisibilityResolved::class);
        $this->builder->setRepository(
            $container->get('oro_visibility.customer_category_repository')
        );
        $subtreeBuilder = new VisibilityChangeCustomerSubtreeCacheBuilder(
            $container->get('doctrine'),
            $container->get('oro_visibility.visibility.resolver.category_visibility_resolver'),
            self::getConfigManager(null),
            $this->scopeManager
        );

        $this->builder->setVisibilityChangeCustomerSubtreeCacheBuilder($subtreeBuilder);
    }

    public function testChangeCustomerCategoryVisibilityToHidden()
    {
        $visibility = new CustomerCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility');
        $em->persist($visibility);
        $em->flush();
        $this->builder->buildCache();
        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeCustomerCategoryVisibilityToHidden
     */
    public function testChangeCustomerCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility');
        $em->flush();
        $this->builder->buildCache();
        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeCustomerCategoryVisibilityToHidden
     */
    public function testChangeCustomerCategoryVisibilityToAll()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CustomerCategoryVisibility::CATEGORY);

        $customerCategoryVisibility = $this->getVisibility();
        $customerCategoryVisibility->setVisibility(CustomerCategoryVisibility::CATEGORY);
        $em = $this->registry->getManagerForClass(CustomerCategoryVisibility::class);
        $em->flush();

        $this->builder->buildCache();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertEquals(
            $customerCategoryVisibility->getVisibility(),
            $visibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_STATIC, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(
            BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE,
            $visibilityResolved['visibility']
        );
    }

    /**
     * @depends testChangeCustomerCategoryVisibilityToAll
     */
    public function testChangeCustomerCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CustomerCategoryVisibility::PARENT_CATEGORY);

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility');
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
     * @depends testChangeCustomerCategoryVisibilityToParentCategory
     */
    public function testChangeCustomerCategoryVisibilityToCustomerGroup()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CustomerCategoryVisibility::CUSTOMER_GROUP);

        $this->assertNotNull($this->getVisibilityResolved());

        $em = $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility');
        $em->flush();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @dataProvider buildCacheDataProvider
     */
    public function testBuildCache(array $expectedVisibilities)
    {
        $expectedVisibilities = $this->replaceReferencesWithIds($expectedVisibilities);
        usort($expectedVisibilities, [$this, 'sortByCategoryAndScope']);

        $this->builder->buildCache();

        $actualVisibilities = $this->getResolvedVisibilities();
        usort($actualVisibilities, [$this, 'sortByCategoryAndScope']);

        $this->assertEquals($expectedVisibilities, $actualVisibilities);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildCacheDataProvider()
    {
        return [
            [
                'expectedVisibilities' => [
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.1'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.2'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.2.1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1'
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.2.1'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1.1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1.1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.2.1.1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.3.1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.3.1'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.3.1'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1_5_6',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customer' => 'customer.level_1.3.1.1'
                    ],
                    [
                        'category' => 'category_1',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.4'
                    ],
                    [
                        'category' => 'category_1_2',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.4'
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.4'
                    ],
                    [
                        'category' => 'category_1_5',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.4'
                    ],
                    [
                        'category' => 'category_1_5_6_7',
                        'visibility' => CustomerCategoryVisibilityResolved::VISIBILITY_VISIBLE,
                        'source' => CustomerCategoryVisibilityResolved::SOURCE_STATIC,
                        'customer' => 'customer.level_1.4'
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $a
     * @param array $b
     * @return int
     */
    protected function sortByCategoryAndScope(array $a, array $b)
    {
        if ($a['category'] == $b['category']) {
            return $a['customer'] <=> $b['customer'];
        }

        return $a['category'] <=> $b['category'];
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

            /** @var Customer $category */
            $customer = $this->getReference($row['customer']);
            $visibilities[$key]['customer'] = $customer->getId();
        }
        return $visibilities;
    }

    /**
     * @return array
     */
    protected function getResolvedVisibilities()
    {
        /** @var CustomerCategoryRepository $repository */
        $repository = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved')
            ->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');

        return $repository
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'IDENTITY(scope.customer) as customer',
                'entity.visibility',
                'entity.source'
            )
            ->join('entity.scope', 'scope')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * @return array
     */
    protected function getVisibilityResolved()
    {
        $em = $this->registry
            ->getManagerForClass('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');
        /** @var CustomerCategoryRepository $repository */
        $repository = $em->getRepository('OroVisibilityBundle:VisibilityResolved\CustomerCategoryVisibilityResolved');
        $qb = $repository
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
     * @return null|CustomerCategoryVisibility
     */
    protected function getVisibility()
    {
        return $this->registry->getManagerForClass('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
            ->getRepository('OroVisibilityBundle:Visibility\CustomerCategoryVisibility')
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
        $this->assertEquals(CustomerCategoryVisibilityResolved::SOURCE_STATIC, $categoryVisibilityResolved['source']);
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }
}

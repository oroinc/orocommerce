<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CustomerCategoryResolvedCacheBuilder;

class CustomerCategoryResolvedCacheBuilderTest extends WebTestCase
{
    use CatalogTrait;

    private const ROOT = 'root';

    private ManagerRegistry $doctrine;
    private Category $category;
    private Scope $scope;
    private CustomerCategoryResolvedCacheBuilder $builder;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrganization::class, LoadCategoryVisibilityData::class]);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $this->doctrine = self::getContainer()->get('doctrine');

        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerCategoryVisibility::VISIBILITY_TYPE,
            ['customer' => $this->getReference('customer.level_1')]
        );

        $this->builder = self::getContainer()->get(
            'oro_visibility.visibility.cache.product.category.customer_category_resolved_cache_builder'
        );
    }

    public function testChangeCustomerCategoryVisibilityToHidden()
    {
        $visibility = new CustomerCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->doctrine->getManagerForClass(CustomerCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerCategoryVisibility::class);
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
        $em = $this->doctrine->getManagerForClass(CustomerCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerCategoryVisibility::class);
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildCacheDataProvider(): array
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

    private function sortByCategoryAndScope(array $a, array $b): int
    {
        if ($a['category'] === $b['category']) {
            return $a['customer'] <=> $b['customer'];
        }

        return $a['category'] <=> $b['category'];
    }

    private function replaceReferencesWithIds(array $visibilities): array
    {
        $rootCategory = $this->getRootCategory();
        foreach ($visibilities as $key => $row) {
            $category = $row['category'];
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

    private function getResolvedVisibilities(): array
    {
        /** @var CustomerCategoryRepository $repository */
        $repository = $this->doctrine->getRepository(CustomerCategoryVisibilityResolved::class);

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

    private function getVisibilityResolved(): ?array
    {
        /** @var CustomerCategoryRepository $repository */
        $repository = $this->doctrine->getRepository(CustomerCategoryVisibilityResolved::class);
        $qb = $repository->createQueryBuilder('customerCategoryVisibilityResolved');

        return $qb->select('customerCategoryVisibilityResolved', 'customerCategoryVisibility')
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
    }

    private function getVisibility(): ?CustomerCategoryVisibility
    {
        return $this->doctrine->getRepository(CustomerCategoryVisibility::class)
            ->findOneBy(['category' => $this->category, 'scope' => $this->scope]);
    }

    private function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        int $expectedVisibility
    ): void {
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

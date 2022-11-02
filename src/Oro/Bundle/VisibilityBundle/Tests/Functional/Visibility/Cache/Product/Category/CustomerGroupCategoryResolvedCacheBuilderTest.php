<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CustomerGroupCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CustomerGroupCategoryResolvedCacheBuilder;

class CustomerGroupCategoryResolvedCacheBuilderTest extends WebTestCase
{
    use CatalogTrait;

    private const ROOT = 'root';

    private ManagerRegistry $doctrine;
    private Category $category;
    private Scope $scope;
    private CustomerGroupCategoryResolvedCacheBuilder $builder;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrganization::class, LoadCategoryVisibilityData::class]);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $this->doctrine = self::getContainer()->get('doctrine');

        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CustomerGroupCategoryVisibility::VISIBILITY_TYPE,
            ['customerGroup' => $this->getReference('customer_group.group3')]
        );

        $this->builder = self::getContainer()->get(
            'oro_visibility.visibility.cache.product.category.customer_group_category_resolved_cache_builder'
        );
    }

    public function testChangeCustomerGroupCategoryVisibilityToHidden()
    {
        $visibility = new CustomerGroupCategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->doctrine->getManagerForClass(CustomerGroupCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerGroupCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerGroupCategoryVisibility::class);
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

        $em = $this->doctrine->getManagerForClass(CustomerGroupCategoryVisibility::class);
        $em->flush();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertNull($visibilityResolved);
    }

    private function getVisibilityResolved(): ?array
    {
        $qb = $this->doctrine->getRepository(CustomerGroupCategoryVisibilityResolved::class)
            ->createQueryBuilder('customerCategoryVisibilityResolved');

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

    private function getVisibility(): ?CustomerGroupCategoryVisibility
    {
        return $this->doctrine->getRepository(CustomerGroupCategoryVisibility::class)
            ->findOneBy(['category' => $this->category, 'scope' => $this->scope]);
    }

    private function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        int $expectedVisibility
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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildCacheDataProvider(): array
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
                        'category' => 'category_1_2_3',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_STATIC,
                        'customerGroup' => 'customer_group.anonymous',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.group1',
                    ],
                    [
                        'category' => 'category_1_2_3_4',
                        'visibility' => CustomerGroupCategoryVisibilityResolved::VISIBILITY_HIDDEN,
                        'source' => CustomerGroupCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
                        'customerGroup' => 'customer_group.anonymous',
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

    private function sortByCategoryAndCustomerGroup(array $a, array $b): int
    {
        if ($a['category'] === $b['category']) {
            return $a['customerGroup'] <=> $b['customerGroup'];
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

            /** @var CustomerGroup $category */
            $customerGroup = $this->getReference($row['customerGroup']);
            $visibilities[$key]['customerGroup'] = $customerGroup->getId();
        }

        return $visibilities;
    }

    private function getResolvedVisibilities(): array
    {
        return $this->doctrine->getRepository(CustomerGroupCategoryVisibilityResolved::class)
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

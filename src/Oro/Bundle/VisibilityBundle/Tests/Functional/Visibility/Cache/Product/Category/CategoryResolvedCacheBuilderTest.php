<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Visibility\Cache\Product\Category;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\BaseCategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\CategoryVisibilityResolved;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadCategoryVisibilityData;
use Oro\Bundle\VisibilityBundle\Visibility\Cache\Product\Category\CategoryResolvedCacheBuilder;

class CategoryResolvedCacheBuilderTest extends WebTestCase
{
    use CatalogTrait;

    private const ROOT = 'root';

    private ManagerRegistry $doctrine;
    private Category $category;
    private Scope $scope;
    private CategoryResolvedCacheBuilder $builder;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadOrganization::class, LoadCategoryVisibilityData::class]);
        self::getContainer()->get('oro_visibility.visibility.cache.cache_builder')->buildCache();

        $this->doctrine = self::getContainer()->get('doctrine');

        $this->category = $this->getReference(LoadCategoryData::SECOND_LEVEL1);

        $this->scope = self::getContainer()->get('oro_scope.scope_manager')->findOrCreate(
            CategoryVisibility::VISIBILITY_TYPE
        );

        $this->builder = self::getContainer()->get(
            'oro_visibility.visibility.cache.product.category.category_resolved_cache_builder'
        );
        $this->builder->buildCache();
    }

    public function testChangeCategoryVisibilityToHidden()
    {
        $visibility = new CategoryVisibility();
        $visibility->setCategory($this->category);
        $visibility->setScope($this->scope);
        $visibility->setVisibility(CategoryVisibility::HIDDEN);

        $em = $this->doctrine->getManagerForClass(CategoryVisibility::class);
        $em->persist($visibility);
        $em->flush();

        $this->builder->buildCache();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_HIDDEN);
    }

    /**
     * @depends testChangeCategoryVisibilityToHidden
     */
    public function testChangeCategoryVisibilityToVisible()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::VISIBLE);

        $em = $this->doctrine->getManagerForClass(CategoryVisibility::class);
        $em->flush();

        $this->builder->buildCache();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertStatic($visibilityResolved, $visibility, BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE);
    }

    /**
     * @depends testChangeCategoryVisibilityToHidden
     */
    public function testChangeCategoryVisibilityToConfig()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::CONFIG);

        $em = $this->doctrine->getManagerForClass(CategoryVisibility::class);
        $em->flush();

        $this->builder->buildCache();

        $this->assertNull($this->getVisibilityResolved());
    }

    /**
     * @depends testChangeCategoryVisibilityToConfig
     */
    public function testChangeCategoryVisibilityToParentCategory()
    {
        $visibility = $this->getVisibility();
        $visibility->setVisibility(CategoryVisibility::PARENT_CATEGORY);

        $em = $this->doctrine->getManagerForClass(CategoryVisibility::class);
        $em->flush();

        $this->builder->buildCache();

        $visibilityResolved = $this->getVisibilityResolved();
        $this->assertNull($visibilityResolved['sourceCategoryVisibility']);
        $this->assertEquals(BaseCategoryVisibilityResolved::SOURCE_PARENT_CATEGORY, $visibilityResolved['source']);
        $this->assertEquals($this->category->getId(), $visibilityResolved['category_id']);
        $this->assertEquals(BaseCategoryVisibilityResolved::VISIBILITY_VISIBLE, $visibilityResolved['visibility']);
    }

    private function getVisibilityResolved(): ?array
    {
        $qb = $this->doctrine->getRepository(CategoryVisibilityResolved::class)
            ->createQueryBuilder('CategoryVisibilityResolved');

        return  $qb->select('CategoryVisibilityResolved', 'CategoryVisibility')
            ->leftJoin('CategoryVisibilityResolved.sourceCategoryVisibility', 'CategoryVisibility')
            ->where(
                $qb->expr()->eq('CategoryVisibilityResolved.category', ':category')
            )
            ->setParameters([
                'category' => $this->category,
            ])
            ->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_ARRAY);
    }

    private function getVisibility(): ?CategoryVisibility
    {
        return $this->doctrine->getRepository(CategoryVisibility::class)
            ->findOneBy(['category' => $this->category]);
    }

    private function assertStatic(
        array $categoryVisibilityResolved,
        VisibilityInterface $categoryVisibility,
        int $expectedVisibility
    ) {
        $this->assertNotNull($categoryVisibilityResolved);
        $this->assertEquals($this->category->getId(), $categoryVisibilityResolved['category_id']);
        $this->assertEquals(CategoryVisibilityResolved::SOURCE_STATIC, $categoryVisibilityResolved['source']);
        $this->assertEquals(
            $categoryVisibility->getVisibility(),
            $categoryVisibilityResolved['sourceCategoryVisibility']['visibility']
        );
        $this->assertEquals($expectedVisibility, $categoryVisibilityResolved['visibility']);
    }

    public function testBuildCache()
    {
        $expectedVisibilities = [
            [
                'category' => self::ROOT,
                'visibility' => CategoryVisibilityResolved::VISIBILITY_FALLBACK_TO_CONFIG,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_2',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_2_3',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_2_3_4',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_5',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_VISIBLE,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
            [
                'category' => 'category_1_5_6',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CategoryVisibilityResolved::SOURCE_STATIC,
            ],
            [
                'category' => 'category_1_5_6_7',
                'visibility' => CategoryVisibilityResolved::VISIBILITY_HIDDEN,
                'source' => CategoryVisibilityResolved::SOURCE_PARENT_CATEGORY,
            ],
        ];
        $expectedVisibilities = $this->replaceReferencesWithIds($expectedVisibilities);
        usort($expectedVisibilities, [$this, 'sortByCategory']);

        $this->builder->buildCache();

        $actualVisibilities = $this->getResolvedVisibilities();
        usort($actualVisibilities, [$this, 'sortByCategory']);

        $this->assertEquals($expectedVisibilities, $actualVisibilities);
    }

    private function sortByCategory(array $a, array $b): int
    {
        return $a['category'] <=> $b['category'];
    }

    private function replaceReferencesWithIds(array $categories): array
    {
        $rootCategory = $this->getRootCategory();

        foreach ($categories as $key => $row) {
            $category = $row['category'];
            if ($category === self::ROOT) {
                $category = $rootCategory;
            } else {
                $category = $this->getReference($category);
            }
            $categories[$key]['category'] = $category->getId();
        }

        return $categories;
    }

    private function getResolvedVisibilities(): array
    {
        return $this->doctrine->getRepository(CategoryVisibilityResolved::class)
            ->createQueryBuilder('entity')
            ->select(
                'IDENTITY(entity.category) as category',
                'entity.visibility',
                'entity.source'
            )
            ->getQuery()
            ->getArrayResult();
    }
}

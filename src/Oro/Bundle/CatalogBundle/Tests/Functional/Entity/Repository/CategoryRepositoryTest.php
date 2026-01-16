<?php

declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\CategoryTitle;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class CategoryRepositoryTest extends WebTestCase
{
    use CatalogTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->loadFixtures([
            LoadOrganization::class,
            LoadMasterCatalogLocalizedTitles::class,
            LoadCategoryData::class,
            LoadCategoryProductData::class,
        ]);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine')->getManagerForClass(Category::class);
    }

    private function getRepository(): CategoryRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(Category::class);
    }

    private function getOrganization(): Organization
    {
        return $this->getReference(LoadOrganization::ORGANIZATION);
    }

    private function findCategoryByTitle(array $categories, string $title): ?Category
    {
        foreach ($categories as $category) {
            if ($category->getDefaultTitle()->getString() === $title) {
                return $category;
            }
        }

        return null;
    }

    public function testGetMasterCatalogRoot()
    {
        $root = $this->getRootCategory();
        $this->assertInstanceOf(Category::class, $root);

        $defaultTitle = $root->getDefaultTitle();
        $this->assertEquals(
            LoadMasterCatalogLocalizedTitles::MASTER_CATALOG_LOCALIZED_TITLES,
            $root->getTitles()->count()
        );
        $this->assertEquals('All Products', $defaultTitle->getString());
    }

    public function testGetChildren()
    {
        $this->getEntityManager()->clear();

        $categories = $this->getRepository()->getChildren();
        $this->assertCount(8, $categories);

        /** @var Category $category */
        $category = current($categories);
        /** @var PersistentCollection $titles */
        $titles = $category->getTitles();
        $this->assertInstanceOf(PersistentCollection::class, $titles);
        $this->assertNotEmpty($titles->toArray());
    }

    public function testGetChildrenIds()
    {
        $this->getEntityManager()->clear();
        /** @var Category $category */
        $categories = $this->getRepository()->findAll();
        $parent = $this->findCategoryByTitle($categories, LoadCategoryData::FIRST_LEVEL);
        $childrenIds = [];
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::SECOND_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::FOURTH_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::SECOND_LEVEL2)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL2)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::FOURTH_LEVEL2)->getId();
        $result = $this->getRepository()->getChildrenIds($parent);
        $this->assertEquals($result, $childrenIds);
    }

    public function testFindOneByDefaultTitle()
    {
        $expectedCategory = $this->getRootCategory();
        $expectedTitle = $expectedCategory->getDefaultTitle()->getString();

        /** @var Category $actualCategory */
        $actualCategory = $this->findCategory($expectedTitle);

        $this->assertInstanceOf(Category::class, $actualCategory);
        $this->assertEquals($expectedCategory->getId(), $actualCategory->getId());
        $this->assertEquals($expectedTitle, $actualCategory->getDefaultTitle()->getString());

        $nonExistsCategory = $this->findCategory('Not existing category');
        $this->assertNull($nonExistsCategory);
    }

    public function testGetCategoryMapByProducts()
    {
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $product3 = $this->getReference(LoadProductData::PRODUCT_5);
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $category2 = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $category3 = $this->getReference(LoadCategoryData::SECOND_LEVEL2);
        $expectedMap = [
            $product1->getId() => $category1,
            $product2->getId() => $category2,
            $product3->getId() => $category3
        ];

        $actualCategory = $this->getRepository()->getCategoryMapByProducts([$product1, $product2, $product3]);
        $this->assertEquals($expectedMap, $actualCategory);
    }

    public function testGetCategoryMapByProductsEmpty()
    {
        $this->assertEmpty($this->getRepository()->getCategoryMapByProducts([]));
    }

    public function testGetProductIdsByCategories()
    {
        $severalCategories[] = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $severalCategories[] = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $severalCategories[] = $this->getReference(LoadCategoryData::FOURTH_LEVEL2);
        $productIds = $this->getRepository()->getProductIdsByCategories($severalCategories);
        $this->assertCount(4, $productIds);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_1)->getId(), $productIds[0]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_2)->getId(), $productIds[1]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_7)->getId(), $productIds[2]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_8)->getId(), $productIds[3]);
    }

    public function testNoOneCategoryInArray()
    {
        $productIds = $this->getRepository()->getProductIdsByCategories([]);
        $this->assertCount(0, $productIds);
    }

    public function testUpdateMaterializedPath()
    {
        /** @var Category $category1 */
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $path = '1_2_3_4';
        $category1->setMaterializedPath($path);
        $this->getRepository()->updateMaterializedPath($category1);
        $category = $this->getRepository()->findOneBy(['id' => $category1->getId(), 'materializedPath' => $path]);
        static::assertNotNull($category);
    }

    public function testFindOneOrNullByDefaultTitleAndParent()
    {
        $category = $this->getRepository()
            ->findOneOrNullByDefaultTitleAndParent(LoadCategoryData::FIRST_LEVEL, $this->getOrganization());

        static::assertSame($this->getReference(LoadCategoryData::FIRST_LEVEL), $category);
    }

    public function testFindOneOrNullByDefaultTitleAndParentWhenParent()
    {
        $category = $this->getRepository()
            ->findOneOrNullByDefaultTitleAndParent(
                LoadCategoryData::THIRD_LEVEL1,
                $this->getOrganization(),
                $this->getReference(LoadCategoryData::SECOND_LEVEL1)
            );

        static::assertSame($this->getReference(LoadCategoryData::THIRD_LEVEL1), $category);
    }

    public function testFindOneOrNullByDefaultTitleAndParentWhenNotExists()
    {
        static::assertNull(
            $this->getRepository()->findOneOrNullByDefaultTitleAndParent('non-existent', $this->getOrganization())
        );
        static::assertNull(
            $this->getRepository()->findOneOrNullByDefaultTitleAndParent(
                'non-existent',
                $this->getOrganization(),
                $this->getReference(LoadCategoryData::SECOND_LEVEL1)
            )
        );
    }

    public function testGetMaxLeft()
    {
        static::assertEquals(11, $this->getRepository()->getMaxLeft());
    }

    public function testFindByDefaultTitleQueryBuilder()
    {
        $expectedCategory = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $expectedTitle = $expectedCategory->getDefaultTitle()->getString();

        $qb = $this->getRepository()->findByDefaultTitleQueryBuilder($expectedTitle);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertEquals($expectedCategory->getId(), $categories[0]->getId());
    }

    public function testFindByDefaultTitleQueryBuilderWhenNotExists()
    {
        $qb = $this->getRepository()->findByDefaultTitleQueryBuilder('Non-existent Category');
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(0, $categories);
    }

    public function testFindByDefaultTitlesPathQueryBuilder()
    {
        $root = $this->getRootCategory();
        $expectedCategory = $this->getReference(LoadCategoryData::THIRD_LEVEL1);

        $pathTitles = [
            'All Products',
            LoadCategoryData::FIRST_LEVEL,
            LoadCategoryData::SECOND_LEVEL1,
            LoadCategoryData::THIRD_LEVEL1,
        ];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertEquals($expectedCategory->getId(), $categories[0]->getId());
    }

    public function testFindByDefaultTitlesPathQueryBuilderWhenNotExists()
    {
        $root = $this->getRootCategory();
        $pathTitles = ['All Products', 'Non-existent', 'Category'];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(0, $categories);
    }

    public function testFindByDefaultTitleQueryBuilderWithMultipleMatches()
    {
        // Create two categories with the same title
        $root = $this->getRootCategory();
        /** @var Category $firstLevel */
        $firstLevel = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        $category1 = new Category();
        $category1->setParentCategory($root);
        $category1->setOrganization($this->getOrganization());
        $category1->addTitle((new CategoryTitle())->setString('Duplicate Title'));

        $category2 = new Category();
        $category2->setParentCategory($firstLevel);
        $category2->setOrganization($this->getOrganization());
        $category2->addTitle((new CategoryTitle())->setString('Duplicate Title'));

        $em = $this->getEntityManager();
        $em->persist($category1);
        $em->persist($category2);
        $em->flush();

        $qb = $this->getRepository()->findByDefaultTitleQueryBuilder('Duplicate Title');
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(2, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertInstanceOf(Category::class, $categories[1]);
    }

    public function testFindByDefaultTitlesPathQueryBuilderForLevelOneCategory()
    {
        $root = $this->getRootCategory();
        $expectedCategory = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        $pathTitles = ['All Products', LoadCategoryData::FIRST_LEVEL];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertEquals($expectedCategory->getId(), $categories[0]->getId());
    }

    public function testFindByDefaultTitlesPathQueryBuilderForRootCategory()
    {
        $root = $this->getRootCategory();

        // Build path for root: just "All Products"
        $pathTitles = ['All Products'];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertEquals($root->getId(), $categories[0]->getId());
    }

    public function testFindByDefaultTitlesPathQueryBuilderWithWrongRoot()
    {
        // Use a non-root category as the "root" parameter
        /** @var Category $wrongRoot */
        $wrongRoot = $this->getReference(LoadCategoryData::FIRST_LEVEL);

        $pathTitles = [
            'All Products',
            LoadCategoryData::FIRST_LEVEL,
            LoadCategoryData::SECOND_LEVEL1,
            LoadCategoryData::THIRD_LEVEL1,
        ];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $wrongRoot);
        $categories = $qb->getQuery()->getResult();

        // Should return no results because the root doesn't match
        $this->assertCount(0, $categories);
    }

    public function testFindByDefaultTitlesPathQueryBuilderWithWrongParentHierarchy()
    {
        $root = $this->getRootCategory();

        // SECOND_LEVEL1 is actually a child of FIRST_LEVEL, not a direct child of root, so this path is incorrect
        $pathTitles = ['All Products', LoadCategoryData::SECOND_LEVEL1];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        // Should return no results because SECOND_LEVEL1 is at level 2, not level 1
        $this->assertCount(0, $categories);
    }

    public function testFindByDefaultTitlesPathQueryBuilderForDeepestLevel()
    {
        $root = $this->getRootCategory();
        $expectedCategory = $this->getReference(LoadCategoryData::FOURTH_LEVEL1);

        $pathTitles = [
            'All Products',
            LoadCategoryData::FIRST_LEVEL,
            LoadCategoryData::SECOND_LEVEL1,
            LoadCategoryData::THIRD_LEVEL1,
            LoadCategoryData::FOURTH_LEVEL1,
        ];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        $this->assertCount(1, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertEquals($expectedCategory->getId(), $categories[0]->getId());
    }

    public function testFindByDefaultTitlesPathQueryBuilderWithDuplicatePaths()
    {
        $root = $this->getRootCategory();

        // Create two categories with identical paths: "All Products / Duplicate Parent / Duplicate Child"
        $parent1 = new Category();
        $parent1->setParentCategory($root);
        $parent1->setOrganization($this->getOrganization());
        $parent1->addTitle((new CategoryTitle())->setString('Duplicate Parent'));

        $child1 = new Category();
        $child1->setParentCategory($parent1);
        $child1->setOrganization($this->getOrganization());
        $child1->addTitle((new CategoryTitle())->setString('Duplicate Child'));

        // Create a second branch with the same path
        $parent2 = new Category();
        $parent2->setParentCategory($root);
        $parent2->setOrganization($this->getOrganization());
        $parent2->addTitle((new CategoryTitle())->setString('Duplicate Parent'));

        $child2 = new Category();
        $child2->setParentCategory($parent2);
        $child2->setOrganization($this->getOrganization());
        $child2->addTitle((new CategoryTitle())->setString('Duplicate Child'));

        $em = $this->getEntityManager();
        $em->persist($parent1);
        $em->persist($child1);
        $em->persist($parent2);
        $em->persist($child2);
        $em->flush();

        $pathTitles = ['All Products', 'Duplicate Parent', 'Duplicate Child'];

        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $root);
        $categories = $qb->getQuery()->getResult();

        // Should return both categories since they have identical paths
        $this->assertCount(2, $categories);
        $this->assertInstanceOf(Category::class, $categories[0]);
        $this->assertInstanceOf(Category::class, $categories[1]);

        // Verify both children are in the results
        $resultIds = [$categories[0]->getId(), $categories[1]->getId()];
        $this->assertContains($child1->getId(), $resultIds);
        $this->assertContains($child2->getId(), $resultIds);
    }

    /**
     * @dataProvider getCategoryPathDataProvider
     */
    public function testGetCategoryPath(?string $categoryReference, array $expectedPath): void
    {
        $category = $categoryReference ? $this->getReference($categoryReference) : $this->getRootCategory();
        $path = $this->getRepository()->getCategoryPath($category);

        $this->assertSame($expectedPath, $path);
    }

    public function getCategoryPathDataProvider(): array
    {
        return [
            'root category' => [
                'categoryReference' => null,
                'expectedPath' => ['All Products'],
            ],
            'first level category' => [
                'categoryReference' => LoadCategoryData::FIRST_LEVEL,
                'expectedPath' => ['All Products', 'category_1'],
            ],
            'second level category' => [
                'categoryReference' => LoadCategoryData::SECOND_LEVEL1,
                'expectedPath' => ['All Products' , 'category_1' , 'category_1_2'],
            ],
            'third level category' => [
                'categoryReference' => LoadCategoryData::THIRD_LEVEL1,
                'expectedPath' => ['All Products' , 'category_1' , 'category_1_2' , 'category_1_2_3'],
            ],
            'fourth level category' => [
                'categoryReference' => LoadCategoryData::FOURTH_LEVEL1,
                'expectedPath' =>
                    ['All Products' , 'category_1' , 'category_1_2' , 'category_1_2_3' , 'category_1_2_3_4'],
            ],
        ];
    }

    /**
     * @dataProvider getCategoryPathDataProvider
     */
    public function testGetCategoryPathRoundTrip(?string $categoryReference, array $expectedPath): void
    {
        $category = $categoryReference ? $this->getReference($categoryReference) : $this->getRootCategory();
        $pathTitles = $this->getRepository()->getCategoryPath($category);

        $this->assertSame($expectedPath, $pathTitles);

        // Use the path to find the category back
        $qb = $this->getRepository()->findByTitlesPathQueryBuilder($pathTitles, $this->getRootCategory());
        $foundCategories = $qb->getQuery()->getResult();

        $this->assertCount(1, $foundCategories);
        $this->assertSame($category->getId(), $foundCategories[0]->getId());
    }
}

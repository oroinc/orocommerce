<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\CatalogBundle\Entity\Category;
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
}

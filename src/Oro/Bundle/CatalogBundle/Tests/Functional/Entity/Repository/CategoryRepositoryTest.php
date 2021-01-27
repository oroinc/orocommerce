<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryRepositoryTest extends WebTestCase
{
    use OrganizationTrait, CatalogTrait;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CategoryRepository
     */
    protected $repository;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroCatalogBundle:Category');
        $this->loadFixtures(
            [
                LoadMasterCatalogLocalizedTitles::class,
                LoadCategoryData::class,
                LoadCategoryProductData::class,
            ]
        );
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
        $this->registry->getManagerForClass('OroCatalogBundle:Category')->clear();

        $categories = $this->repository->getChildren();
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
        $this->registry->getManagerForClass('OroCatalogBundle:Category')->clear();
        /** @var Category $category */
        $categories = $this->repository->findAll();
        $parent = $this->findCategoryByTitle($categories, LoadCategoryData::FIRST_LEVEL);
        $childrenIds = [];
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::SECOND_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::FOURTH_LEVEL1)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::SECOND_LEVEL2)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL2)->getId();
        $childrenIds[] = $this->findCategoryByTitle($categories, LoadCategoryData::FOURTH_LEVEL2)->getId();
        $result = $this->repository->getChildrenIds($parent);
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

        $actualCategory = $this->repository->getCategoryMapByProducts([$product1, $product2, $product3]);
        $this->assertEquals($expectedMap, $actualCategory);
    }

    public function testGetCategoryMapByProductsEmpty()
    {
        $this->assertEmpty($this->repository->getCategoryMapByProducts([]));
    }

    /**
     * @param $categories Category[]
     * @param $title string
     * @return Category
     */
    protected function findCategoryByTitle($categories, $title)
    {
        foreach ($categories as $category) {
            if ($category->getDefaultTitle()->getString() == $title) {
                return $category;
            }
        }

        return null;
    }

    public function testGetProductIdsByCategories()
    {
        $severalCategories[] = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $severalCategories[] = $this->getReference(LoadCategoryData::SECOND_LEVEL1);
        $severalCategories[] = $this->getReference(LoadCategoryData::FOURTH_LEVEL2);
        $productIds = $this->repository->getProductIdsByCategories($severalCategories);
        $this->assertCount(4, $productIds);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_1)->getId(), $productIds[0]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_2)->getId(), $productIds[1]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_7)->getId(), $productIds[2]);
        $this->assertEquals($this->getReference(LoadProductData::PRODUCT_8)->getId(), $productIds[3]);
    }

    public function testNoOneCategoryInArray()
    {
        $productIds = $this->repository->getProductIdsByCategories([]);
        $this->assertCount(0, $productIds);
    }

    public function testUpdateMaterializedPath()
    {
        /** @var Category $category1 */
        $category1 = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $path = '1_2_3_4';
        $category1->setMaterializedPath($path);
        $this->repository->updateMaterializedPath($category1);
        $category = $this->repository->findOneBy(['id' => $category1->getId(), 'materializedPath' => $path]);
        static::assertNotNull($category);
    }

    public function testFindOneOrNullByDefaultTitleAndParent()
    {
        $category = $this->repository
            ->findOneOrNullByDefaultTitleAndParent(LoadCategoryData::FIRST_LEVEL, $this->getOrganization());

        static::assertSame($this->getReference(LoadCategoryData::FIRST_LEVEL), $category);
    }

    public function testFindOneOrNullByDefaultTitleAndParentWhenParent()
    {
        $category = $this->repository
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
            $this->repository->findOneOrNullByDefaultTitleAndParent('non-existent', $this->getOrganization())
        );
        static::assertNull(
            $this->repository->findOneOrNullByDefaultTitleAndParent(
                'non-existent',
                $this->getOrganization(),
                $this->getReference(LoadCategoryData::SECOND_LEVEL1)
            )
        );
    }

    public function testGetMaxLeft()
    {
        static::assertEquals(11, $this->repository->getMaxLeft());
    }
}

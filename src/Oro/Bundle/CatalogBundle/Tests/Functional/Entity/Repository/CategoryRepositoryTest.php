<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

/**
 * @dbIsolation
 */
class CategoryRepositoryTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CategoryRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroCatalogBundle:Category');
        $this->loadFixtures([LoadCategoryData::class, LoadCategoryProductData::class]);
    }

    public function testGetMasterCatalogRoot()
    {
        $root = $this->repository->getMasterCatalogRoot();
        $this->assertInstanceOf('Oro\Bundle\CatalogBundle\Entity\Category', $root);

        $defaultTitle = $root->getDefaultTitle();
        $this->assertEquals('Master catalog', $defaultTitle->getString());
    }

    public function testGetChildrenWithTitles()
    {
        $this->registry->getManagerForClass('OroCatalogBundle:Category')->clear();

        $categories = $this->repository->getChildrenWithTitles();
        $this->assertCount(8, $categories);

        /** @var Category $category */
        $category = current($categories);
        /** @var PersistentCollection $titles */
        $titles = $category->getTitles();
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $titles);
        $this->assertTrue($titles->isInitialized());
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
        $expectedCategory = $this->repository->getMasterCatalogRoot();
        $expectedTitle = $expectedCategory->getDefaultTitle()->getString();

        $actualCategory = $this->repository->findOneByDefaultTitle($expectedTitle);
        $this->assertInstanceOf('Oro\Bundle\CatalogBundle\Entity\Category', $actualCategory);
        $this->assertEquals($expectedCategory->getId(), $actualCategory->getId());
        $this->assertEquals($expectedTitle, $actualCategory->getDefaultTitle()->getString());

        $this->assertNull($this->repository->findOneByDefaultTitle('Not existing category'));
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
}

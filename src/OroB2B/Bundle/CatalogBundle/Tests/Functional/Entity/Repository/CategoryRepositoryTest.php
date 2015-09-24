<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;

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
        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroB2BCatalogBundle:Category');
        $this->loadFixtures(
            [
                'OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData',
            ]
        );
    }

    public function testGetMasterCatalogRoot()
    {
        $root = $this->repository->getMasterCatalogRoot();
        $this->assertInstanceOf('OroB2B\Bundle\CatalogBundle\Entity\Category', $root);

        $defaultTitle = $root->getDefaultTitle();
        $this->assertEquals('Master catalog', $defaultTitle->getString());
    }

    public function testGetChildrenWithTitles()
    {
        $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')->clear();

        $categories = $this->repository->getChildrenWithTitles();
        $this->assertCount(5, $categories);

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
        $this->registry->getManagerForClass('OroB2BCatalogBundle:Category')->clear();
        /** @var Category $category */
        $categories = $this->repository->findAll();
        $parent = $this->findCategoryByTitle($categories, LoadCategoryData::FIRST_LEVEL);
        $childrenId1 = $this->findCategoryByTitle($categories, LoadCategoryData::SECOND_LEVEL1)->getId();
        $childrenId2 = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL1)->getId();
        $childrenId3 = $this->findCategoryByTitle($categories, LoadCategoryData::THIRD_LEVEL2)->getId();
        $result = $this->repository->getChildrenIds($parent);
        $this->assertEquals($result, [$childrenId1, $childrenId2, $childrenId3]);
    }

    public function testFindOneByDefaultTitle()
    {
        $expectedCategory = $this->repository->getMasterCatalogRoot();
        $expectedTitle = $expectedCategory->getDefaultTitle()->getString();

        $actualCategory = $this->repository->findOneByDefaultTitle($expectedTitle);
        $this->assertInstanceOf('OroB2B\Bundle\CatalogBundle\Entity\Category', $actualCategory);
        $this->assertEquals($expectedCategory, $actualCategory);
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
    }
}

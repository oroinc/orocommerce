<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\CatalogBundle\Entity\Category;
use OroB2B\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;

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
        $this->assertCount(1, $categories);

        /** @var Category $category */
        $category = current($categories);
        /** @var PersistentCollection $titles */
        $titles = $category->getTitles();
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $titles);
        $this->assertTrue($titles->isInitialized());
        $this->assertNotEmpty($titles->toArray());
    }
}

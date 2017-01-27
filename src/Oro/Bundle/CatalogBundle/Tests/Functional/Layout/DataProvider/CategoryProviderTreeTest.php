<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Util\Debug;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CategoryProviderTreeTest extends WebTestCase
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var CategoryRepository
     */
    protected $repository;

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadMasterCatalogLocalizedTitles::class,
                LoadCategoryData::class,
                LoadCategoryProductData::class,
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroCatalogBundle:Category');
    }

    /**
     * Returns categoryProvider for given category identifier
     *
     * @param int $node_id
     * @return CategoryProvider
     */
    private function getCategoryProviderForNode($node_id)
    {
        $requestProductHandler = $this->createMock(RequestProductHandler::class);
        $requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($node_id);

        return new CategoryProvider(
            $requestProductHandler,
            $this->repository,
            $this->createMock(CategoryTreeProvider::class)
        );
    }

    /**
     * Test if methods returns correct path from category_1_2_3 to root node
     */
    public function testGetParentTraverseToRootCategories()
    {
        $category_1_2_3_id = $this->repository->findOneByDefaultTitle('category_1_2_3')->getId();
        $categoryProvider = $this->getCategoryProviderForNode($category_1_2_3_id);
        $parents = $categoryProvider->getParentCategories();

        $this->assertCount(3, $parents);

        $level2 = array_pop($parents);
        $this->assertEquals('category_1_2', $level2->getTitle());
        $level1 = array_pop($parents);
        $this->assertEquals('category_1', $level1->getTitle());
        $level0 = array_pop($parents);
        $this->assertEquals('Products categories', $level0->getTitle());

        $this->assertCount(0, $parents);
    }

    /**
     * Test if getParentCategories called on root category returns empty array
     */
    public function testGetParentRootHasNoPath()
    {
        $root = $this->repository->getMasterCatalogRoot();
        $categoryProvider = $this->getCategoryProviderForNode($root->getId());
        $parents = $categoryProvider->getParentCategories();

        $this->assertTrue(is_array($parents));
        $this->assertCount(0, $parents);
    }
}

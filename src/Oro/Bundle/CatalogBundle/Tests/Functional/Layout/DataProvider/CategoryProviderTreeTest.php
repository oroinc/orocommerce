<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;

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

    /**
     * @var WebsiteManager
     */
    private $websiteManager;

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
        $this->websiteManager = $this->getContainer()->get('oro_website.manager');
    }

    /**
     * Returns categoryProvider for given category identifier
     *
     * @param int $nodeId
     * @return CategoryProvider
     */
    private function getCategoryProviderForNode($nodeId)
    {
        $requestProductHandler = $this->createMock(RequestProductHandler::class);
        $requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($nodeId);

        return new CategoryProvider(
            $requestProductHandler,
            $this->repository,
            $this->createMock(CategoryTreeProvider::class),
            $this->websiteManager
        );
    }

    /**
     * Test if methods returns correct path from category_1_2_3 to root node
     */
    public function testGetParentTraverseToRootCategories()
    {
        $organizationRepo = $this->getContainer()
            ->get('doctrine')
            ->getRepository(Organization::class);

        $defaultOrganization = $organizationRepo->getFirst();

        $categoryId = $this->repository->findOneByDefaultTitle('category_1_2_3', $defaultOrganization)->getId();
        $categoryProvider = $this->getCategoryProviderForNode($categoryId);
        $parents = $categoryProvider->getParentCategories();

        $this->assertCount(3, $parents);

        $level2 = array_pop($parents);
        $this->assertEquals('category_1_2', $level2->getTitle());
        $level1 = array_pop($parents);
        $this->assertEquals('category_1', $level1->getTitle());
        $level0 = array_pop($parents);
        $this->assertEquals('All Products', $level0->getTitle());

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

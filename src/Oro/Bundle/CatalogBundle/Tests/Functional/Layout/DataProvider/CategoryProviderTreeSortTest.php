<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadUnsortedCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryProviderTreeSortTest extends WebTestCase
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
     * @var CategoryTreeProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryTreeProvider;

    protected function setUp()
    {
        $this->initClient();

        $this->loadFixtures(
            [
                LoadUnsortedCategoryData::class,
            ]
        );

        $this->registry = $this->getContainer()->get('doctrine');
        $this->repository = $this->registry->getRepository('OroCatalogBundle:Category');
    }

    public function testSortAlphabetically()
    {
        $root = $this->repository->getMasterCatalogRoot();
        $categoryProvider = $this->getCategoryProvider();

        $categories = $this->repository->getChildren($root, false, 'left', 'ASC', true);
        $this->categoryTreeProvider->expects($this->once())
            ->method('getCategories')
            ->willReturn($categories);

        $treeArray = $categoryProvider->getCategoryTreeArray();

        $this->assertTrue(is_array($treeArray));
        $this->assertCount(2, $treeArray);
        $this->assertEquals(LoadUnsortedCategoryData::FIRST_LEVEL2, $treeArray[0]['title']);
        $this->assertEquals(LoadUnsortedCategoryData::FIRST_LEVEL1, $treeArray[1]['title']);
    }

    /**
     * Returns categoryProvider for given category identifier
     *
     * @return CategoryProvider
     */
    protected function getCategoryProvider()
    {
        $requestProductHandler = $this->createMock(RequestProductHandler::class);

        $this->categoryTreeProvider = $this->createMock(CategoryTreeProvider::class);

        $provider = new CategoryProvider(
            $requestProductHandler,
            $this->repository,
            $this->categoryTreeProvider
        );

        $provider->setLocalizationHelper($this->getContainer()->get('oro_locale.helper.localization'));

        return $provider;
    }
}

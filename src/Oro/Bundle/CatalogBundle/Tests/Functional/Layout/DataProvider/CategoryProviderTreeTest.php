<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CatalogBundle\Entity\Repository\CategoryRepository;
use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProviderInterface;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\OrganizationBundle\Tests\Functional\OrganizationTrait;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryProviderTreeTest extends WebTestCase
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

    /**
     * @var CategoryProvider
     */
    protected $categoryProvider;

    /**
     * @var TokenAccessorInterface
     */
    private $tokenAccessor;

    /**
     * @var LocalizationHelper
     */
    private $localizationHelper;

    protected function setUp(): void
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
        $this->tokenAccessor = $this->getContainer()->get('oro_security.token_accessor');
        $this->localizationHelper = $this->getContainer()->get('oro_locale.helper.localization');
    }

    /**
     * Returns categoryProvider for given category identifier
     *
     * @param int $nodeId
     * @return CategoryProvider
     */
    private function getCategoryProviderForNode($nodeId)
    {
        /** @var MasterCatalogRootProviderInterface $masterCatalogRootProvider */
        $masterCatalogRootProvider = $this->getContainer()->get('oro_catalog.provider.master_catalog_root');

        $requestProductHandler = $this->createMock(RequestProductHandler::class);
        $requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($nodeId);

        return new CategoryProvider(
            $requestProductHandler,
            $this->registry,
            $this->createMock(CategoryTreeProvider::class),
            $this->tokenAccessor,
            $this->localizationHelper,
            $masterCatalogRootProvider
        );
    }

    /**
     * Test if methods returns correct path from category_1_2_3 to root node
     */
    public function testGetParentTraverseToRootCategories()
    {
        $category = $this->findCategory('category_1_2_3');
        $categoryId = $category->getId();
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
        $categoryProvider = $this->getCategoryProviderForNode($this->getRootCategory()->getId());
        $parents = $categoryProvider->getParentCategories();

        $this->assertTrue(is_array($parents));
        $this->assertCount(0, $parents);
    }
}

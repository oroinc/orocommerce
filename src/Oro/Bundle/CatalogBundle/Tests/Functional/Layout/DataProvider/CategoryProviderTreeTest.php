<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadMasterCatalogLocalizedTitles;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryProviderTreeTest extends WebTestCase
{
    use CatalogTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadMasterCatalogLocalizedTitles::class,
            LoadCategoryData::class,
            LoadCategoryProductData::class,
        ]);
    }

    private function getCategoryProviderForNode(int $nodeId): CategoryProvider
    {
        $requestProductHandler = $this->createMock(RequestProductHandler::class);
        $requestProductHandler->expects($this->once())
            ->method('getCategoryId')
            ->willReturn($nodeId);

        return new CategoryProvider(
            $requestProductHandler,
            self::getContainer()->get('doctrine'),
            $this->createMock(CategoryTreeProvider::class),
            self::getContainer()->get('oro_security.token_accessor'),
            self::getContainer()->get('oro_locale.helper.localization'),
            self::getContainer()->get('oro_catalog.provider.master_catalog_root')
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

        $this->assertIsArray($parents);
        $this->assertCount(0, $parents);
    }
}

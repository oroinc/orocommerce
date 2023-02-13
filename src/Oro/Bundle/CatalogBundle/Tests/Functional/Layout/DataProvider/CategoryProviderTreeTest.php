<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Layout\DataProvider;

use Oro\Bundle\CatalogBundle\Handler\RequestProductHandler;
use Oro\Bundle\CatalogBundle\Layout\DataProvider\CategoryProvider;
use Oro\Bundle\CatalogBundle\Provider\CategoryTreeProvider;
use Oro\Bundle\CatalogBundle\Tests\Functional\CatalogTrait;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CategoryProviderTreeTest extends WebTestCase
{
    use CatalogTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadCategoryData::class,
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
            self::getContainer()->get('oro_catalog.provider.master_catalog_root')
        );
    }

    public function testGetCurrentCategoryById(): void
    {
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        $categoryProvider = $this->getCategoryProviderForNode($category->getId());

        self::assertEquals($category, $categoryProvider->getCurrentCategory());
    }

    public function testGetCurrentCategoryByMasterCatalogRoot(): void
    {
        $categoryProvider = $this->getCategoryProviderForNode(0);

        self::assertEquals($this->getRootCategory(), $categoryProvider->getCurrentCategory());
    }
}

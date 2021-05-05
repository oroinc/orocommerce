<?php

namespace Oro\Bundle\CatalogBundle\Tests\Unit\ImportExport\TemplateFixture;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\ImportExport\TemplateFixture\CategoryFixture;
use Oro\Bundle\CatalogBundle\Provider\MasterCatalogRootProvider;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRegistry;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateEntityRepositoryInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class CategoryFixtureTest extends \PHPUnit\Framework\TestCase
{
    /** @var TemplateManager */
    private $templateManager;

    /** @var MasterCatalogRootProvider */
    private $masterCatalogRootProvider;

    /** @var CategoryFixture */
    private $fixture;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    protected function setUp(): void
    {
        $this->templateManager = $this->createMock(TemplateManager::class);
        $this->masterCatalogRootProvider = $this->createMock(MasterCatalogRootProvider::class);

        $this->localizationManager = $this->createMock(LocalizationManager::class);
        $this->fixture = new CategoryFixture($this->localizationManager);
        $this->fixture->setMasterCatalogRootProvider($this->masterCatalogRootProvider);
        $this->fixture->setTemplateManager($this->templateManager);
    }

    public function testGetEntityClass(): void
    {
        $this->assertEquals(Category::class, $this->fixture->getEntityClass());
    }

    public function testGetEntityWhenExists(): void
    {
        $this->templateManager
            ->method('getEntityRegistry')
            ->willReturn($templateEntityRegistry = $this->createMock(TemplateEntityRegistry::class));

        $templateEntityRegistry
            ->method('hasEntity')
            ->with(Category::class, $key = 'sample_key')
            ->willReturn(true);

        $templateEntityRegistry
            ->method('getEntity')
            ->with(Category::class, $key)
            ->willReturn($category = new Category());

        $this->assertEquals(
            $category,
            $this->fixture->getEntity($key)
        );
    }

    public function testGetEntityWhenNotExists(): void
    {
        $this->templateManager
            ->method('getEntityRegistry')
            ->willReturn($templateEntityRegistry = $this->createMock(TemplateEntityRegistry::class));

        $templateEntityRegistry
            ->method('hasEntity')
            ->with(Category::class, $key = 'sample_key')
            ->willReturn(false);

        $templateEntityRegistry
            ->expects($this->once())
            ->method('addEntity')
            ->with(Category::class, $key, $this->isInstanceOf(Category::class))
            ->willReturn(true);

        $templateEntityRegistry
            ->method('getEntity')
            ->with(Category::class, $key)
            ->willReturn($category = new Category());

        $this->assertEquals(
            $category,
            $this->fixture->getEntity($key)
        );
    }

    public function testFillEntityDataWhenInvalidKey(): void
    {
        $this->templateManager
            ->method('getEntityRepository')
            ->with(Organization::class)
            ->willReturn($organizationRepo = $this->createMock(TemplateEntityRepositoryInterface::class));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Unknown entity: "' . Category::class . '"; key: "' . ($key = 'invalid key') . '".'
        );

        $this->fixture->fillEntityData($key, new Category());
    }
}

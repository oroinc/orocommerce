<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\EventListener\ManuallyAddedProductCollectionIndexerListener;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;

class ManuallyAddedProductCollectionIndexerListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var WebsiteContextManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteContextManager;

    /**
     * @var ContentVariantProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentVariantProvider;

    /**
     * @var ProductCollectionDefinitionConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $productCollectionDefinitionConverter;

    /**
     * @var ManuallyAddedProductCollectionIndexerListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->websiteContextManager = $this->createMock(WebsiteContextManager::class);
        $this->contentVariantProvider = $this->createMock(ContentVariantProviderInterface::class);
        $this->productCollectionDefinitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);
        $this->listener = new ManuallyAddedProductCollectionIndexerListener(
            $this->registry,
            $this->configManager,
            $this->websiteContextManager,
            $this->contentVariantProvider,
            $this->productCollectionDefinitionConverter
        );
    }

    public function testOnWebsiteSearchIndexWhenNotSupportedFieldsGroup()
    {
        $context = [AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image']];
        $event = new IndexEntityEvent(\stdClass::class, [], $context);

        $this->websiteContextManager->expects($this->never())
            ->method($this->anything());
        $this->contentVariantProvider->expects($this->never())
            ->method($this->anything());
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->configManager->expects($this->never())
            ->method($this->anything());
        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method($this->anything());

        $this->listener->onWebsiteSearchIndex($event);
        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWhenNoWebsiteId(array $context)
    {
        $event = new IndexEntityEvent(\stdClass::class, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn(null);

        $this->contentVariantProvider->expects($this->never())
            ->method('isSupportedClass');
        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->configManager->expects($this->never())
            ->method('get');
        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');

        $this->listener->onWebsiteSearchIndex($event);
        self::assertTrue($event->isPropagationStopped());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWhenNoSupportedClass(array $context)
    {
        $websiteId = 42;
        $entityClass = \stdClass::class;
        $context['websiteId'] = $websiteId;
        $event = new IndexEntityEvent($entityClass, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn($websiteId);
        $this->contentVariantProvider->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(false);

        $this->registry->expects($this->never())
            ->method('getManagerForClass');
        $this->configManager->expects($this->never())
            ->method('get');
        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');

        $this->listener->onWebsiteSearchIndex($event);
        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWhenNoWebCatalogId(array $context)
    {
        $websiteId = 42;
        $entityClass = Product::class;
        $context['websiteId'] = $websiteId;
        $event = new IndexEntityEvent($entityClass, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn($websiteId);
        $this->contentVariantProvider->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(true);
        $website = new Website();
        $websiteRepository = $this->createMock(ObjectRepository::class);
        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);
        $websiteManager = $this->createMock(ObjectManager::class);
        $websiteManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Website::class)
            ->willReturn($websiteManager);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn(null);

        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');

        $this->listener->onWebsiteSearchIndex($event);
        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWhenNoWebCatalog(array $context)
    {
        $websiteId = 42;
        $entityClass = Product::class;
        $context['websiteId'] = $websiteId;
        $event = new IndexEntityEvent($entityClass, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn($websiteId);
        $this->contentVariantProvider->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(true);
        $website = new Website();
        $websiteRepository = $this->createMock(ObjectRepository::class);
        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);
        $websiteManager = $this->createMock(ObjectManager::class);
        $websiteManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);
        $webCatalogId = 777;
        $webCatalogRepository = $this->createMock(ObjectRepository::class);
        $webCatalogRepository->expects($this->once())
            ->method('find')
            ->with($webCatalogId)
            ->willReturn(null);
        $webCatalogManager = $this->createMock(ObjectManager::class);
        $webCatalogManager->expects($this->once())
            ->method('getRepository')
            ->with(WebCatalog::class)
            ->willReturn($webCatalogRepository);
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [Website::class],
                [WebCatalog::class]
            )
            ->willReturnOnConsecutiveCalls($websiteManager, $webCatalogManager);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalogId);

        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');

        $this->listener->onWebsiteSearchIndex($event);
        self::assertFalse($event->isPropagationStopped());
    }

    /**
     * @dataProvider validContextDataProvider
     */
    public function testOnWebsiteSearchIndexWhenEmptyVariantsByRecordId(array $context)
    {
        $websiteId = 42;
        $entityClass = Product::class;
        $context['websiteId'] = $websiteId;
        $event = new IndexEntityEvent($entityClass, [], $context);

        $this->websiteContextManager->expects($this->once())
            ->method('getWebsiteId')
            ->with($context)
            ->willReturn($websiteId);
        $this->contentVariantProvider->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(true);
        $website = new Website();
        $websiteRepository = $this->createMock(ObjectRepository::class);
        $websiteRepository->expects($this->once())
            ->method('find')
            ->with($websiteId)
            ->willReturn($website);
        $websiteManager = $this->createMock(ObjectManager::class);
        $websiteManager->expects($this->once())
            ->method('getRepository')
            ->with(Website::class)
            ->willReturn($websiteRepository);
        $webCatalogId = 777;
        $webCatalog = new WebCatalog();
        $webCatalogRepository = $this->createMock(ObjectRepository::class);
        $webCatalogRepository->expects($this->once())
            ->method('find')
            ->with($webCatalogId)
            ->willReturn($webCatalog);
        $webCatalogManager = $this->createMock(ObjectManager::class);
        $webCatalogManager->expects($this->once())
            ->method('getRepository')
            ->with(WebCatalog::class)
            ->willReturn($webCatalogRepository);
        $this->registry->expects($this->exactly(2))
            ->method('getManagerForClass')
            ->withConsecutive(
                [Website::class],
                [WebCatalog::class]
            )
            ->willReturnOnConsecutiveCalls($websiteManager, $webCatalogManager);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_web_catalog.web_catalog', false, false, $website)
            ->willReturn($webCatalogId);

        $this->productCollectionDefinitionConverter->expects($this->never())
            ->method('getDefinitionParts');

        $this->listener->onWebsiteSearchIndex($event);
        self::assertFalse($event->isPropagationStopped());
    }

    public function validContextDataProvider(): \Generator
    {
        yield [[]];
        yield [[AbstractIndexer::CONTEXT_FIELD_GROUPS => ['main']]];
    }
}

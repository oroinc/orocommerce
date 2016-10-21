<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\PageProvider;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\PageProvider\WebCatalogPageProviderRegistry;
use Oro\Bundle\WebCatalogBundle\PageProvider\WebCatalogPageProvider;
use Oro\Component\WebCatalog\PageProviderInterface;

class WebCatalogPageProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebCatalogPageProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $providerRegistry;

    /**
     * @var WebCatalogPageProvider
     */
    protected $webCatalogPageProvider;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMock(WebCatalogPageProviderRegistry::class);
        $this->webCatalogPageProvider = new WebCatalogPageProvider($this->providerRegistry);
    }

    protected function tearDown()
    {
        unset($this->providerRegistry, $this->webCatalogPageProvider);
    }

    public function testSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->getMock(PageProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->getMock(PageProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(true);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->assertTrue($this->webCatalogPageProvider->isSupportedClass($className));
    }

    public function testNotSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->getMock(PageProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->getMock(PageProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->assertFalse($this->webCatalogPageProvider->isSupportedClass($className));
    }

    public function testGetContentVariantsByEntity()
    {
        $entity = new \stdClass();

        $provider1ContentVariant1 = new ContentVariant();
        $provider1ContentVariant2 = new ContentVariant();

        $provider2ContentVariant1 = new ContentVariant();

        $expectedVariants = [
            $provider1ContentVariant1,
            $provider1ContentVariant2,
            $provider2ContentVariant1
        ];

        $provider1 = $this->getMock(PageProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getContentVariantsByEntity')
            ->with($entity)
            ->willReturn([
                $provider1ContentVariant1,
                $provider1ContentVariant2
            ]);

        $provider2 = $this->getMock(PageProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getContentVariantsByEntity')
            ->with($entity)
            ->willReturn([
                $provider2ContentVariant1
            ]);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $actual = $this->webCatalogPageProvider->getContentVariantsByEntity($entity);

        $this->assertCount(count($expectedVariants), $actual);

        foreach ($expectedVariants as $expectedVariant) {
            $this->assertContains($expectedVariant, $actual);
        }
    }

    public function testGetContentVariantsByEntities()
    {
        $firstEntityId = 123;
        $secondEntityId = 42;

        $firstEntity = new \stdClass();
        $firstEntity->id = $firstEntityId;

        $secondEntity = new \stdClass();
        $secondEntity->id = $secondEntityId;

        $contentVariant1 = new ContentVariant();
        $contentVariant1->setType('page_type_1');

        $contentVariant2 = new ContentVariant();
        $contentVariant2->setType('page_type_2');

        $contentVariant3 = new ContentVariant();
        $contentVariant3->setType('page_type_3');

        $entities = [
            $firstEntity,
            $secondEntity
        ];

        $expectedVariants = [
            $firstEntityId => [
                $contentVariant1,
                $contentVariant3
            ],
            $secondEntityId => [
                $contentVariant2
            ]
        ];

        $provider1 = $this->getMock(PageProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getContentVariantsByEntities')
            ->with($entities)
            ->willReturn([
                $firstEntityId => [$contentVariant1],
                $secondEntityId => [$contentVariant2]
            ]);

        $provider2 = $this->getMock(PageProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getContentVariantsByEntities')
            ->with($entities)
            ->willReturn([
                $firstEntityId => [$contentVariant3],
            ]);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $actual = $this->webCatalogPageProvider->getContentVariantsByEntities($entities);

        foreach ($expectedVariants as $entityId => $contentVariants) {
            $this->assertEquals($contentVariants, $actual[$entityId]);
        }
    }
}

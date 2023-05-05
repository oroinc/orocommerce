<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Provider;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WebCatalogBundle\Provider\ContentVariantProvider;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentVariantProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(true);

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $this->assertTrue($contentVariantProvider->isSupportedClass($className));
    }

    public function testNotSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $this->assertFalse($contentVariantProvider->isSupportedClass($className));
    }

    public function testModifyNodeQueryBuilderByEntities()
    {
        $entities = [new \stdClass()];
        $entityClass = \stdClass::class;
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(true);
        $provider1->expects($this->once())
            ->method('modifyNodeQueryBuilderByEntities')
            ->with($queryBuilder, $entityClass, $entities);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(false);
        $provider2->expects($this->never())
            ->method('modifyNodeQueryBuilderByEntities');

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $contentVariantProvider->modifyNodeQueryBuilderByEntities($queryBuilder, $entityClass, $entities);
    }

    public function testGetValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getValues')
            ->with($node)
            ->willReturn(['first' => 1]);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getValues')
            ->with($node)
            ->willReturn(['second' => 2]);

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $this->assertEquals(
            ['first' => 1, 'second' => 2],
            $contentVariantProvider->getValues($node)
        );
    }

    public function testGetLocalizedValues()
    {
        $node = $this->createMock(ContentNodeInterface::class);

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getLocalizedValues')
            ->with($node)
            ->willReturn(['first' => 1]);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getLocalizedValues')
            ->with($node)
            ->willReturn(['second' => 2]);

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $this->assertEquals(
            ['first' => 1, 'second' => 2],
            $contentVariantProvider->getLocalizedValues($node)
        );
    }

    public function testGetRecordId()
    {
        $id = 42;
        $item = ['key' => 'value'];

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn(null);

        $provider2 = $this->createMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn($id);

        $contentVariantProvider = new ContentVariantProvider([$provider1, $provider2]);
        $this->assertEquals($id, $contentVariantProvider->getRecordId($item));
    }

    public function testGetRecordIdNoId()
    {
        $item = ['key' => 'value'];

        $provider1 = $this->createMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn(null);

        $contentVariantProvider = new ContentVariantProvider([$provider1]);
        $this->assertNull($contentVariantProvider->getRecordId($item));
    }
}

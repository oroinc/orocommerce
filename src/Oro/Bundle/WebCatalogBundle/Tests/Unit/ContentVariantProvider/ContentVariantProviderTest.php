<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\ContentVariantProvider;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebCatalogBundle\ContentVariantProvider\ContentVariantProviderRegistry;
use Oro\Bundle\WebCatalogBundle\ContentVariantProvider\ContentVariantProvider;
use Oro\Component\WebCatalog\ContentVariantProviderInterface;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

class ContentVariantProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ContentVariantProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $providerRegistry;

    /**
     * @var ContentVariantProvider
     */
    protected $contentVariantProvider;

    protected function setUp()
    {
        $this->providerRegistry = $this->getMock(ContentVariantProviderRegistry::class);
        $this->contentVariantProvider = new ContentVariantProvider($this->providerRegistry);
    }

    protected function tearDown()
    {
        unset($this->providerRegistry, $this->contentVariantProvider);
    }

    public function testSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
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

        $this->assertTrue($this->contentVariantProvider->isSupportedClass($className));
    }

    public function testNotSupportedClass()
    {
        $className = 'stdClass';

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($className)
            ->willReturn(false);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
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

        $this->assertFalse($this->contentVariantProvider->isSupportedClass($className));
    }

    public function testModifyNodeQueryBuilderByEntities()
    {
        $entities = [new \stdClass()];
        $entityClass = \stdClass::class;
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(true);
        $provider1->expects($this->once())
            ->method('modifyNodeQueryBuilderByEntities')
            ->with($queryBuilder, $entityClass, $entities);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('isSupportedClass')
            ->with($entityClass)
            ->willReturn(false);
        $provider2->expects($this->never())
            ->method('modifyNodeQueryBuilderByEntities');

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->contentVariantProvider->modifyNodeQueryBuilderByEntities($queryBuilder, $entityClass, $entities);
    }

    public function testGetValues()
    {
        /** @var ContentNodeInterface $node */
        $node = $this->getMock(ContentNodeInterface::class);

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getValues')
            ->with($node)
            ->willReturn(['first' => 1]);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getValues')
            ->with($node)
            ->willReturn(['second' => 2]);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->assertEquals(
            ['first' => 1, 'second' => 2],
            $this->contentVariantProvider->getValues($node)
        );
    }

    public function testGetLocalizedValues()
    {
        /** @var ContentNodeInterface $node */
        $node = $this->getMock(ContentNodeInterface::class);

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getLocalizedValues')
            ->with($node)
            ->willReturn(['first' => 1]);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getLocalizedValues')
            ->with($node)
            ->willReturn(['second' => 2]);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->assertEquals(
            ['first' => 1, 'second' => 2],
            $this->contentVariantProvider->getLocalizedValues($node)
        );
    }

    public function testGetRecordId()
    {
        $id = 42;
        $item = ['key' => 'value'];

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn(null);

        $provider2 = $this->getMock(ContentVariantProviderInterface::class);
        $provider2->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn($id);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([
                $provider1,
                $provider2
            ]);

        $this->assertEquals($id, $this->contentVariantProvider->getRecordId($item));
    }

    public function testGetRecordIdNoId()
    {
        $item = ['key' => 'value'];

        $provider1 = $this->getMock(ContentVariantProviderInterface::class);
        $provider1->expects($this->once())
            ->method('getRecordId')
            ->with($item)
            ->willReturn(null);

        $this->providerRegistry->expects($this->once())
            ->method('getProviders')
            ->willReturn([$provider1]);

        $this->assertNull($this->contentVariantProvider->getRecordId($item));
    }
}

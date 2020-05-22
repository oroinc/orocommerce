<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\Registry;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantTypeHandlerRegistry;

class ProductVariantTypeHandlerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantTypeHandlerRegistry */
    protected $registry;

    protected function setUp(): void
    {
        $this->registry = new ProductVariantTypeHandlerRegistry();
    }

    public function testGetVariantTypeHandlers()
    {
        $this->assertEmpty($this->registry->getVariantTypeHandlers());
    }

    public function testAddHandler()
    {
        $handler = $this->createTypeHandler('type1');
        $this->registry->addHandler($handler);

        $actualHandlers = $this->registry->getVariantTypeHandlers();
        $this->assertCount(1, $actualHandlers);
        $this->assertContains($handler, $actualHandlers);
    }

    public function testGetVariantTypeHandler()
    {
        $typeName = 'type';
        $knownTypeHandler = $this->createTypeHandler($typeName);
        $this->registry->addHandler($knownTypeHandler);

        $actualType = $this->registry->getVariantTypeHandler($typeName);

        $this->assertSame($knownTypeHandler, $actualType);
    }

    public function testGetVariantTypeHandlerWithUnknownType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Handler for type "unknown" was not found. Known types: type1, type2');

        $knownTypeHandler1 = $this->createTypeHandler('type1');
        $knownTypeHandler2= $this->createTypeHandler('type2');
        $this->registry->addHandler($knownTypeHandler1);
        $this->registry->addHandler($knownTypeHandler2);

        $this->registry->getVariantTypeHandler('unknown');
    }

    /**
     * @param string $type
     * @return ProductVariantTypeHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createTypeHandler($type)
    {
        $handler = $this->createMock(ProductVariantTypeHandlerInterface::class);
        $handler->expects($this->any())
            ->method('getType')
            ->willReturn($type);

        return $handler;
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\Registry;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;

class ProductVariantFieldValueHandlerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVariantFieldValueHandlerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->registry = new ProductVariantFieldValueHandlerRegistry();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->registry);
    }

    public function testGetVariantFieldValueHandlers()
    {
        $this->assertEmpty($this->registry->getVariantFieldValueHandlers());
    }

    public function testAddHandler()
    {
        $handler = $this->createHandler('type1');
        $this->registry->addHandler($handler);

        $actualHandlers = $this->registry->getVariantFieldValueHandlers();
        $this->assertCount(1, $actualHandlers);
        $this->assertContains($handler, $actualHandlers);
    }

    public function testGetVariantTypeHandler()
    {
        $typeName = 'type';
        $knownTypeHandler = $this->createHandler($typeName);
        $this->registry->addHandler($knownTypeHandler);

        $actualType = $this->registry->getVariantFieldValueHandler($typeName);

        $this->assertSame($knownTypeHandler, $actualType);
    }

    public function testGetVariantTypeHandlerWithUnknownType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Value handler "unknown" for variant field was not found. Known types: type1, type2'
        );

        $knownTypeHandler1 = $this->createHandler('type1');
        $knownTypeHandler2 = $this->createHandler('type2');
        $this->registry->addHandler($knownTypeHandler1);
        $this->registry->addHandler($knownTypeHandler2);

        $this->registry->getVariantFieldValueHandler('unknown');
    }

    /**
     * @param string $type
     * @return ProductVariantFieldValueHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createHandler($type)
    {
        $handler = $this->createMock(ProductVariantFieldValueHandlerInterface::class);
        $handler->expects($this->any())
            ->method('getType')
            ->willReturn($type);

        return $handler;
    }
}

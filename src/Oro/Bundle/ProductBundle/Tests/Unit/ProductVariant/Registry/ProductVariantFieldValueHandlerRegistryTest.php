<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ProductVariant\Registry;

use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerRegistry;
use Oro\Bundle\ProductBundle\ProductVariant\Registry\ProductVariantFieldValueHandlerInterface;

class ProductVariantFieldValueHandlerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ProductVariantFieldValueHandlerRegistry */
    protected $registry;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = new ProductVariantFieldValueHandlerRegistry();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Value handler "unknown" for variant field was not found. Known types: type1, type2
     */
    public function testGetVariantTypeHandlerWithUnknownType()
    {
        $knownTypeHandler1 = $this->createHandler('type1');
        $knownTypeHandler2 = $this->createHandler('type2');
        $this->registry->addHandler($knownTypeHandler1);
        $this->registry->addHandler($knownTypeHandler2);

        $this->registry->getVariantFieldValueHandler('unknown');
    }

    /**
     * @param string $type
     * @return ProductVariantFieldValueHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
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

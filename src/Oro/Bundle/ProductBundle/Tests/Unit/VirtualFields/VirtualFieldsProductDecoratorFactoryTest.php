<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\VirtualFields;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Symfony\Contracts\Cache\CacheInterface;

class VirtualFieldsProductDecoratorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /** @var VirtualFieldsSelectQueryConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $converterMock;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineMock;

    /** @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldHelperMock;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject  */
    private $attributeProvider;

    /** @var VirtualFieldsProductDecoratorFactory */
    private $testedVirtualFieldsProductDecoratorFactory;

    protected function setUp(): void
    {
        $this->converterMock = $this->createMock(VirtualFieldsSelectQueryConverter::class);
        $this->doctrineMock = $this->createMock(ManagerRegistry::class);
        $this->fieldHelperMock = $this->createMock(FieldHelper::class);
        $this->cacheProvider = $this->createMock(CacheInterface::class);
        $this->attributeProvider = $this->createMock(ConfigProvider::class);

        $this->testedVirtualFieldsProductDecoratorFactory = new VirtualFieldsProductDecoratorFactory(
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock,
            $this->cacheProvider,
            $this->attributeProvider,
        );
    }

    public function testCreateDecoratedProduct()
    {
        $product = $this->createMock(Product::class);
        $products = [
            $this->createMock(Product::class),
            $this->createMock(Product::class),
            $this->createMock(Product::class),
        ];

        $expectedProduct = new VirtualFieldsProductDecorator(
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock,
            $this->cacheProvider,
            $this->attributeProvider,
            $products,
            $product
        );

        $actualProduct = $this->testedVirtualFieldsProductDecoratorFactory
            ->createDecoratedProduct($products, $product);

        $this->assertEquals($expectedProduct, $actualProduct);
    }
}

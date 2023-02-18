<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\VirtualFields;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class VirtualFieldsProductDecoratorFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var VirtualFieldsProductDecoratorFactory
     */
    private $testedVirtualFieldsProductDecoratorFactory;

    /**
     * @var VirtualFieldsSelectQueryConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $converterMock;

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineMock;

    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldHelperMock;

    /**
     * @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject  */
    private $attributeProvider;

    protected function setUp(): void
    {
        $this->converterMock = $this->createMock(VirtualFieldsSelectQueryConverter::class);
        $this->doctrineMock = $this->createMock(ManagerRegistry::class);
        $this->fieldHelperMock = $this->createMock(FieldHelper::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->attributeProvider = $this->createMock(ConfigProvider::class);

        $this->testedVirtualFieldsProductDecoratorFactory = new VirtualFieldsProductDecoratorFactory(
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock,
            $this->cacheProvider,
        );
        $this->testedVirtualFieldsProductDecoratorFactory->setAttributeProvider($this->attributeProvider);
    }

    /**
     * @param Product[] $products
     * @param Product $product
     *
     * @return VirtualFieldsProductDecorator
     */
    private function createExpectedProductDecorator(array $products, $product)
    {
        $decorator = new VirtualFieldsProductDecorator(
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock,
            $this->cacheProvider,
            $products,
            $product
        );
        $decorator->setAttributeProvider($this->attributeProvider);
        return $decorator;
    }

    public function testCreateDecoratedProduct()
    {
        $productMock = $this->createMock(Product::class);

        $productsMocks = [
            $this->createMock(Product::class),
            $this->createMock(Product::class),
            $this->createMock(Product::class),
        ];

        $expectedProduct = $this->createExpectedProductDecorator($productsMocks, $productMock);

        $actualProduct = $this->testedVirtualFieldsProductDecoratorFactory
            ->createDecoratedProduct($productsMocks, $productMock);

        $this->assertEquals($expectedProduct, $actualProduct);
    }
}

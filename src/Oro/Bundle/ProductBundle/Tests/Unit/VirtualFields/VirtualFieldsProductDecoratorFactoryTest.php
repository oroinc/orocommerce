<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\VirtualFields;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\VirtualFields\QueryDesigner\VirtualFieldsSelectQueryConverter;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecorator;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class VirtualFieldsProductDecoratorFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var VirtualFieldsProductDecoratorFactory
     */
    private $testedVirtualFieldsProductDecoratorFactory;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityFieldProviderMock;

    /**
     * @var VirtualFieldsSelectQueryConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $converterMock;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineMock;

    /**
     * @var FieldHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldHelperMock;

    public function setUp()
    {
        $this->entityFieldProviderMock = $this->createMock(EntityFieldProvider::class);
        $this->converterMock = $this->createMock(VirtualFieldsSelectQueryConverter::class);
        $this->doctrineMock = $this->createMock(ManagerRegistry::class);
        $this->fieldHelperMock = $this->createMock(FieldHelper::class);

        $this->testedVirtualFieldsProductDecoratorFactory = new VirtualFieldsProductDecoratorFactory(
            $this->entityFieldProviderMock,
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock
        );
    }

    /**
     * @return Product|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProductMock()
    {
        return $this->createMock(Product::class);
    }

    /**
     * @return ProductHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createProductHolderMock()
    {
        return $this->createMock(ProductHolderInterface::class);
    }

    /**
     * @param Product[] $products
     * @param Product $product
     *
     * @return VirtualFieldsProductDecorator
     */
    private function createExpectedProductDecorator(array $products, $product)
    {
        return new VirtualFieldsProductDecorator(
            $this->entityFieldProviderMock,
            $this->converterMock,
            $this->doctrineMock,
            $this->fieldHelperMock,
            $products,
            $product
        );
    }

    public function testCreateDecoratedProduct()
    {
        $productMock = $this->createProductMock();

        $productsMocks = [
            $this->createProductMock(),
            $this->createProductMock(),
            $this->createProductMock(),
        ];

        $expectedProduct = $this->createExpectedProductDecorator($productsMocks, $productMock);

        $actualProduct = $this->testedVirtualFieldsProductDecoratorFactory
            ->createDecoratedProduct($productsMocks, $productMock);

        $this->assertEquals($expectedProduct, $actualProduct);
    }

    public function testCreateDecoratedProductByProductHolders()
    {
        $productMock = $this->createProductMock();
        $productMockForHolder = $this->createProductMock();
        $productHolderMock = $this->createProductHolderMock();
        $productHoldersMocks = [$productHolderMock];
        $productMocks = [$productMockForHolder];

        $productHolderMock
            ->expects($this->once())
            ->method('getProduct')
            ->willReturn($productMockForHolder);

        $expectedProduct = $this->createExpectedProductDecorator($productMocks, $productMock);

        $actualProduct = $this->testedVirtualFieldsProductDecoratorFactory->createDecoratedProductByProductHolders(
            $productHoldersMocks,
            $productMock
        );

        $this->assertEquals($expectedProduct, $actualProduct);
    }
}

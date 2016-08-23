<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;

class ShippingContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /** @var ShippingContext */
    protected $model;

    protected function setUp()
    {
        $this->model = new ShippingContext();
    }

    protected function tearDown()
    {
        unset($this->model);
    }

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            $this->model,
            [
                ['lineItems', []],
                ['billingAddress', new AddressStub()],
                ['shippingAddress', new AddressStub()],
                ['shippingOrigin', new AddressStub()],
                ['paymentMethod', ''],
                ['currency', ''],
                ['subtotal', new Price()],
            ]
        );
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getFreightClassesProvider
     */
    public function testSetLineItems(array $inputData, array $expectedData)
    {
        $this->model->setLineItems($inputData);

        $this->assertEquals($expectedData, $this->model->getLineItems());
    }

    /**
     * @return array
     */
    public function getFreightClassesProvider()
    {
        $entityIdentifier = 2;
        $product = new Product();
        $product->setSku('test sku');
        $productUnit = new ProductUnit();
        $productUnit->setCode('kg')->setDefaultPrecision(3);
        $price = new Price();
        $weight = new Weight();
        $dimensions = new Dimensions();
        $quantity = 1;

        $shippingLineItem = $this->getMockBuilder(ShippingLineItemInterface::class)->getMock();
        $shippingLineItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($product);
        $shippingLineItem->expects($this->any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $shippingLineItem->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);
        $shippingLineItem->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions);
        $shippingLineItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $shippingLineItem->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);
        $shippingLineItem->expects($this->once())
            ->method('getWeight')
            ->willReturn($weight);

        $productUnitHolder = $this->getMockBuilder(ProductUnitHolderInterface::class)->getMock();
        $productUnitHolder->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productUnitHolder->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $productHolder = $this->getMockBuilder(ProductHolderInterface::class)->getMock();
        $productHolder->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $productHolder->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn($entityIdentifier);

        $productShippingOptions = $this->getMockBuilder(ProductShippingOptionsInterface::class)->getMock();
        $productShippingOptions->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $productShippingOptions->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productShippingOptions->expects($this->once())
            ->method('getWeight')
            ->willReturn($weight);
        $productShippingOptions->expects($this->once())
            ->method('getDimensions')
            ->willReturn($dimensions);

        $quantityAware = $this->getMockBuilder(QuantityAwareInterface::class)->getMock();
        $quantityAware->expects($this->once())
            ->method('getQuantity')
            ->willReturn($quantity);
        $priceAware = $this->getMockBuilder(PriceAwareInterface::class)->getMock();
        $priceAware->expects($this->once())
            ->method('getPrice')
            ->willReturn($price);

        return [
            'no data' => [
                'input' => [
                ],
                'expected' => [
                ],
            ],
            'without interfaces' => [
                'input' => [
                    'no data'
                ],
                'expected' => [
                    new ShippingLineItem()
                ],
            ],
            'ShippingContextInterface' => [
                'input' => [
                    $shippingLineItem
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setEntityIdentifier($entityIdentifier)
                        ->setDimensions($dimensions)
                        ->setQuantity($quantity)
                        ->setPrice($price)
                        ->setWeight($weight)
                ],
            ],
            'ProductUnitHolder' => [
                'input' => [
                    $productUnitHolder
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setProductUnit($productUnit)
                        ->setEntityIdentifier($entityIdentifier)
                ],
            ],
            'ProductHolderInterface' => [
                'input' => [
                    $productHolder
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setProduct($product)
                        ->setEntityIdentifier($entityIdentifier)
                ],
            ],
            'ProductShippingOptions' => [
                'input' => [
                    $productShippingOptions
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setProduct($product)
                        ->setProductUnit($productUnit)
                        ->setWeight($weight)
                        ->setDimensions($dimensions)
                ],
            ],
            '$quantityAware' => [
                'input' => [
                    $quantityAware
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setQuantity($quantity)
                ],
            ],
            'PriceAwareInterface' => [
                'input' => [
                    $priceAware
                ],
                'expected' => [
                    (new ShippingLineItem())
                        ->setPrice($price)
                ],
            ],
        ];
    }
}

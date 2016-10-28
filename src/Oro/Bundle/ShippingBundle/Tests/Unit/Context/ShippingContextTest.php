<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Context;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Entity\PriceAwareInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use Oro\Bundle\ProductBundle\Model\QuantityAwareInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItemInterface;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptionsInterface;
use Oro\Bundle\ShippingBundle\Model\Dimensions;
use Oro\Bundle\ShippingBundle\Model\Weight;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingContextTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @var ShippingContext
     */
    protected $model;

    /**
     * @var Product
     */
    protected $product;

    /**
     * @var ProductUnit
     */
    private $productUnit;

    /**
     * @var Price
     */
    private $price;

    /**
     * @var Weight
     */
    private $weight;

    /**
     * @var ProductHolderInterface
     */
    private $productHolder;

    /**
     * @var Dimensions
     */
    private $dimensions;

    /**
     * @var integer
     */
    private $quantity;

    protected function setUp()
    {
        $this->model = new ShippingContext();

        $this->productHolder = $this->getMockForAbstractClass(ProductHolderInterface::class);
        $this->productHolder->expects($this->any())
            ->method('getEntityIdentifier')
            ->willReturn('test');
        $this->product = new Product();
        $this->product->setSku('test sku');
        $this->productUnit = new ProductUnit();
        $this->productUnit->setCode('kg')->setDefaultPrecision(3);
        $this->price = new Price();
        $this->weight = new Weight();
        $this->dimensions = new Dimensions();
        $this->quantity = 1;
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

    public function testSetLineItemsWithShippingContextInterface()
    {
        $mockItem = $this->getMockBuilder(ShippingLineItemInterface::class)->getMock();
        $mockItem->expects($this->any())
            ->method('getProduct')
            ->willReturn($this->product);
        $mockItem->expects($this->any())
            ->method('getProductUnit')
            ->willReturn($this->productUnit);
        $mockItem->expects($this->once())
            ->method('getDimensions')
            ->willReturn($this->dimensions);
        $mockItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn($this->quantity);
        $mockItem->expects($this->once())
            ->method('getPrice')
            ->willReturn($this->price);
        $mockItem->expects($this->once())
            ->method('getWeight')
            ->willReturn($this->weight);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setProduct($this->product)
            ->setProductUnit($this->productUnit)
            ->setProductHolder($mockItem)
            ->setDimensions($this->dimensions)
            ->setQuantity($this->quantity)
            ->setPrice($this->price)
            ->setWeight($this->weight);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    public function testSetLineItemsWithProductUnitHolderInterface()
    {
        $mockItem = $this->getMockBuilder(ProductUnitHolderInterface::class)->getMock();
        $mockItem->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($this->productUnit);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setProductUnit($this->productUnit);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    public function testSetLineItemsWithProductHolderInterface()
    {
        $mockItem = $this->getMockBuilder(ProductHolderInterface::class)->getMock();
        $mockItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setProduct($this->product)
            ->setProductHolder($mockItem);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    public function testSetLineItemsWithProductShippingOptionsInterface()
    {
        $mockItem = $this->getMockBuilder(ProductShippingOptionsInterface::class)->getMock();
        $mockItem->expects($this->once())
            ->method('getProduct')
            ->willReturn($this->product);
        $mockItem->expects($this->once())
            ->method('getProductUnit')
            ->willReturn($this->productUnit);
        $mockItem->expects($this->once())
            ->method('getWeight')
            ->willReturn($this->weight);
        $mockItem->expects($this->once())
            ->method('getDimensions')
            ->willReturn($this->dimensions);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setProduct($this->product)
            ->setProductUnit($this->productUnit)
            ->setWeight($this->weight)
            ->setDimensions($this->dimensions);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    public function testSetLineItemsWithQuantityAwareInterface()
    {
        $mockItem = $this->getMockBuilder(QuantityAwareInterface::class)->getMock();
        $mockItem->expects($this->once())
            ->method('getQuantity')
            ->willReturn($this->quantity);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setQuantity($this->quantity);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    public function testSetLineItemsWithPriceAwareInterface()
    {
        $mockItem = $this->getMockBuilder(PriceAwareInterface::class)->getMock();
        $mockItem->expects($this->once())
            ->method('getPrice')
            ->willReturn($this->price);

        $this->model->setLineItems([$mockItem]);

        $shippingLineItem = (new ShippingLineItem())
            ->setPrice($this->price);

        $this->assertEquals([$shippingLineItem], $this->model->getLineItems());
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getDataProvider
     */
    public function testSetLineItems(array $inputData, array $expectedData)
    {
        $this->model->setLineItems($inputData);

        $this->assertEquals($expectedData, $this->model->getLineItems());
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'no data'            => [
                'input'    => [
                ],
                'expected' => [
                ],
            ],
            'without interfaces' => [
                'input'    => [
                    'no data'
                ],
                'expected' => [
                    new ShippingLineItem()
                ],
            ]
        ];
    }
}

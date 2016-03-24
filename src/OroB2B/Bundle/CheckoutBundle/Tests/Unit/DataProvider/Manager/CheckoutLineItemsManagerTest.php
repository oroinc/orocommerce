<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Manager;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemsManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutLineItemsConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsConverter;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    protected function setUp()
    {
        $this->checkoutLineItemsConverter = $this
            ->getMock('OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter');

        $this->checkoutLineItemsManager = new CheckoutLineItemsManager($this->checkoutLineItemsConverter);
    }

    protected function tearDown()
    {
        unset($this->checkoutLineItemsConverter, $this->checkoutLineItemsManager);
    }

    public function testAddProvider()
    {
        /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

        $this->checkoutLineItemsManager->addProvider($provider);

        $this->assertAttributeSame(
            [$provider],
            'providers',
            $this->checkoutLineItemsManager
        );
    }

    /**
     * @dataProvider getDataDataProvider
     * @param bool $withDataProvider
     * @param bool $isEntitySupported
     */
    public function testGetData($withDataProvider, $isEntitySupported)
    {
        $entity = new \stdClass();

        /** @var CheckoutSource|\PHPUnit_Framework_MockObject_MockObject $checkoutSource */
        $checkoutSource = $this->getMock('OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource');
        $checkoutSource->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $checkout = new Checkout();
        $checkout->setSource($checkoutSource);

        $expected = false;

        if ($withDataProvider) {
            /** @var CheckoutDataProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
            $provider = $this->getMock('OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface');

            if ($isEntitySupported) {
                $provider->expects($this->once())
                    ->method('isEntitySupported')
                    ->with($entity)
                    ->willReturn(true);

                $product = (new Product())->setSku('product');
                $productUnit = new ProductUnit();
                $productUnit->setCode('item');
                $quantity = 100;
                $price = new Price();

                $data = [
                    [
                        'product' => $product,
                        'productSku' => $product->getSku(),
                        'quantity' => $quantity,
                        'productUnit' => $productUnit,
                        'productUnitCode' => $productUnit->getCode(),
                        'price' => $price
                    ]
                ];

                $provider->expects($this->any())
                    ->method('getData')
                    ->with($entity)
                    ->willReturn($data);

                $expected = new ArrayCollection([
                    (new OrderLineItem())->setProduct($product)
                        ->setProductSku($product->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode($productUnit->getCode())
                        ->setPrice($price)
                ]);
                $this->checkoutLineItemsConverter->expects($this->once())
                    ->method('convert')
                    ->with($data)
                    ->willReturn($expected);
            }

            $this->checkoutLineItemsManager->addProvider($provider);
        }

        $result = $this->checkoutLineItemsManager->getData($checkout);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'without data providers' => [
                'withDataProvider' => false,
                'isEntitySupported' => false
            ],
            'not supported entity' => [
                'withDataProvider' => true,
                'isEntitySupported' => false
            ],
            'supported entity' => [
                'withDataProvider' => true,
                'isEntitySupported' => true
            ]
        ];
    }
}

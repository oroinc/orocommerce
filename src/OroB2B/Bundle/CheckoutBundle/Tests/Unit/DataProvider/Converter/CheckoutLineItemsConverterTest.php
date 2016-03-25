<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class CheckoutLineItemsConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutLineItemsConverter
     */
    protected $checkoutLineItemsConverter;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->checkoutLineItemsConverter = new CheckoutLineItemsConverter();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->checkoutLineItemsConverter);
    }

    /**
     * @dataProvider convertDataProvider
     * @param array $data
     * @param ArrayCollection $expected
     */
    public function testConvert(array $data, ArrayCollection $expected)
    {
        $result = $this->checkoutLineItemsConverter->convert($data);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function convertDataProvider()
    {
        $product1 = (new Product())->setSku('product1');
        $product2 = (new Product())->setSku('product2');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');
        $quantity = 10;
        $price = new Price();

        return [
            [
                'data' => [],
                'expected' => new ArrayCollection([])
            ],
            [
                'data' => [
                    [
                        'product' => $product1,
                        'productSku' => $product1->getSku(),
                        'quantity' => $quantity,
                        'productUnit' => $productUnit,
                        'productUnitCode' => $productUnit->getCode(),
                        'price' => $price
                    ],
                    [
                        'product' => $product2,
                        'productSku' => $product2->getSku(),
                        'quantity' => $quantity,
                        'productUnit' => $productUnit,
                        'productUnitCode' => $productUnit->getCode(),
                        'price' => $price
                    ]

                ],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setProduct($product1)
                        ->setProductSku($product1->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode($productUnit->getCode())
                        ->setPrice($price),
                    (new OrderLineItem())->setProduct($product2)
                        ->setProductSku($product2->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode($productUnit->getCode())
                        ->setPrice($price)
                ])
            ],
        ];
    }
}

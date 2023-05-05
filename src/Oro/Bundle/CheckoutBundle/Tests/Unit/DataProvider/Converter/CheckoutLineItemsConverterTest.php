<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class CheckoutLineItemsConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutLineItemsConverter */
    private $checkoutLineItemsConverter;

    protected function setUp(): void
    {
        $this->checkoutLineItemsConverter = new CheckoutLineItemsConverter();
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(array $data, ArrayCollection $expected)
    {
        $result = $this->checkoutLineItemsConverter->convert($data);

        $this->assertEquals($expected, $result);
    }

    public function convertDataProvider(): array
    {
        $product1 = (new Product())->setSku('product1');
        $product2 = (new Product())->setSku('product2');
        $productUnit = new ProductUnit();
        $productUnit->setCode('item');
        $quantity = 10;
        $price = new Price();
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return [
            'empty data' => [
                'data' => [],
                'expected' => new ArrayCollection([])
            ],
            'data with empty item' => [
                'data' => [[]],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())
                ])
            ],
            'normal data' => [
                'data' => [
                    [
                        'product' => $product1,
                        'productSku' => $product1->getSku(),
                        'quantity' => $quantity,
                        'freeFormProduct' => 'test1',
                        'productUnit' => $productUnit,
                        'productUnitCode' => $productUnit->getCode(),
                        'price' => $price
                    ],
                    [
                        'product' => $product2,
                        'productSku' => $product2->getSku(),
                        'quantity' => $quantity,
                        'freeFormProduct' => 'test2',
                        'productUnit' => $productUnit,
                        'productUnitCode' => $productUnit->getCode(),
                        'price' => $price,
                        'priceType' => OrderLineItem::PRICE_TYPE_BUNDLED,
                        'shipBy' => $now,
                        'fromExternalSource' => true,
                        'comment' => 'Comment'
                    ]

                ],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setProduct($product1)
                        ->setProductSku($product1->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode($productUnit->getCode())
                        ->setFreeFormProduct('test1')
                        ->setPrice($price),
                    (new OrderLineItem())->setProduct($product2)
                        ->setProductSku($product2->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($productUnit)
                        ->setProductUnitCode($productUnit->getCode())
                        ->setFreeFormProduct('test2')
                        ->setPrice($price)
                        ->setPriceType(OrderLineItem::PRICE_TYPE_BUNDLED)
                        ->setShipBy($now)
                        ->setFromExternalSource(true)
                        ->setComment('Comment')
                ])
            ],
            'data with non-existent property' => [
                'data' => [[
                    'nonExistentProperty' => 'sampleValue',
                    'comment' => 'Comment',
                ]],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setComment('Comment'),
                ]),
            ],
            'data with null property' => [
                'data' => [[
                    'shipBy' => $now,
                    'comment' => null,
                ]],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setShipBy($now),
                ]),
            ],
        ];
    }
}

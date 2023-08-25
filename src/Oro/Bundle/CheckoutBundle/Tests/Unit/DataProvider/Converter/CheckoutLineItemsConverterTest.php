<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use PHPUnit\Framework\TestCase;

class CheckoutLineItemsConverterTest extends TestCase
{
    private CheckoutLineItemsConverter $checkoutLineItemsConverter;

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

        self::assertEquals($expected, $result);
    }

    public function testReflectionClassInConvertCalled(): void
    {
        $reflectionClassMock = $this->createMock(EntityReflectionClass::class);
        $reflectionMethodMock = $this->createMock(\ReflectionMethod::class);

        $reflectionMethodMock
            ->expects(self::once())
            ->method('invoke');

        $reflectionClassMock
            ->expects(self::once())
            ->method('hasProperty')
            ->with('product')
            ->willReturn(true);

        $reflectionClassMock
            ->expects(self::once())
            ->method('getMethod')
            ->with('setProduct')
            ->willReturn($reflectionMethodMock);

        (new \ReflectionObject($this->checkoutLineItemsConverter))
            ->getProperty('reflectionClass')
            ->setValue($this->checkoutLineItemsConverter, $reflectionClassMock);

        $data = [
            ['product' => (new Product())->setSku('product1')]
        ];

        $this->checkoutLineItemsConverter->convert($data);
    }

    public function testReflectionClassInConvertNotCalled(): void
    {
        $reflectionClassMock = $this->createMock(EntityReflectionClass::class);
        $reflectionMethodMock = $this->createMock(\ReflectionMethod::class);

        $reflectionMethodMock
            ->expects(self::never())
            ->method('invoke');

        $reflectionClassMock
            ->expects(self::never())
            ->method('hasProperty')
            ->with('product')
            ->willReturn(true);

        $reflectionClassMock
            ->expects(self::never())
            ->method('getMethod')
            ->with('setProduct')
            ->willReturn($reflectionMethodMock);

        (new \ReflectionObject($this->checkoutLineItemsConverter))
            ->getProperty('reflectionClass')
            ->setValue($this->checkoutLineItemsConverter, $reflectionClassMock);

        $data = [
            ['product' => null]
        ];

        $this->checkoutLineItemsConverter->convert($data);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
            'data with not exists field' => [
                'data' => [
                    [
                        'product' => $product1,
                        'notExistsField' => 'Test'
                    ]
                ],
                'expected' => new ArrayCollection([(new OrderLineItem())->setProduct($product1)])
            ],
            'data with null field' => [
                'data' => [
                    [
                        'product' => $product1,
                        'price' => null
                    ]
                ],
                'expected' => new ArrayCollection([(new OrderLineItem())->setProduct($product1)])
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

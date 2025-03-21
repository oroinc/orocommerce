<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\DataProvider\Converter;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutLineItemsConverter;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\EntityReflectionClass;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Entity\OrderProductKitItemLineItem;
use Oro\Bundle\PricingBundle\Entity\PriceTypeAwareInterface;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ProductKitItemStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\TestCase;

class CheckoutLineItemsConverterTest extends TestCase
{
    private CheckoutLineItemsConverter $checkoutLineItemsConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->checkoutLineItemsConverter = new CheckoutLineItemsConverter();
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(array $data, ArrayCollection $expected): void
    {
        self::assertEquals($expected, $this->checkoutLineItemsConverter->convert($data));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function convertDataProvider(): array
    {
        $product1 = (new Product())->setSku('product1');
        $product2 = (new Product())->setSku('product2');
        $product3 = (new Product())->setSku('product3');
        $kitItem = new ProductKitItemStub();
        $unitItem = (new ProductUnit())->setCode('item');
        $unitEach = (new ProductUnit())->setCode('each');
        $quantity = 10;
        $kitItemLineItemQuantity = 3;
        $price = Price::create(34.5678, 'USD');
        $kitItemLineItemPrice = Price::create(12.3456, 'USD');
        $now = new \DateTime('now', new \DateTimeZone('UTC'));

        return [
            'empty data' => [
                'data' => [],
                'expected' => new ArrayCollection([])
            ],
            'data with empty item' => [
                'data' => [[]],
                'expected' => new ArrayCollection([new OrderLineItem()])
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
                        'productUnit' => $unitItem,
                        'productUnitCode' => $unitItem->getCode(),
                        'price' => $price
                    ],
                    [
                        'product' => $product2,
                        'productSku' => $product2->getSku(),
                        'quantity' => $quantity,
                        'freeFormProduct' => 'test2',
                        'productUnit' => $unitItem,
                        'productUnitCode' => $unitItem->getCode(),
                        'price' => $price,
                        'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
                        'shipBy' => $now,
                        'fromExternalSource' => true,
                        'comment' => 'Comment'
                    ]

                ],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setProduct($product1)
                        ->setProductSku($product1->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($unitItem)
                        ->setProductUnitCode($unitItem->getCode())
                        ->setFreeFormProduct('test1')
                        ->setPrice($price),
                    (new OrderLineItem())->setProduct($product2)
                        ->setProductSku($product2->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($unitItem)
                        ->setProductUnitCode($unitItem->getCode())
                        ->setFreeFormProduct('test2')
                        ->setPrice($price)
                        ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED)
                        ->setShipBy($now)
                        ->setFromExternalSource(true)
                        ->setComment('Comment')
                ])
            ],
            'kit line item data' => [
                'data' => [
                    [
                        'product' => $product1,
                        'productSku' => $product1->getSku(),
                        'quantity' => $quantity,
                        'freeFormProduct' => '',
                        'productUnit' => $unitItem,
                        'productUnitCode' => $unitItem->getCode(),
                        'price' => $price,
                        'kitItemLineItems' => [
                            [
                                'kitItem' => $kitItem,
                                'product' => $product3,
                                'productSku' => $product3->getSku(),
                                'quantity' => $kitItemLineItemQuantity,
                                'productUnit' => $unitEach,
                                'productUnitCode' => $unitEach->getCode(),
                                'price' => $kitItemLineItemPrice,
                                'nonExistentProperty' => 'sample data'
                            ]
                        ]
                    ],
                    [
                        'product' => $product2,
                        'productSku' => $product2->getSku(),
                        'quantity' => $quantity,
                        'freeFormProduct' => 'test2',
                        'productUnit' => $unitItem,
                        'productUnitCode' => $unitItem->getCode(),
                        'price' => $price,
                        'priceType' => PriceTypeAwareInterface::PRICE_TYPE_BUNDLED,
                        'shipBy' => $now,
                        'fromExternalSource' => true,
                        'comment' => 'Comment'
                    ]
                ],
                'expected' => new ArrayCollection([
                    (new OrderLineItem())->setProduct($product1)
                        ->setProductSku($product1->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($unitItem)
                        ->setProductUnitCode($unitItem->getCode())
                        ->setFreeFormProduct('')
                        ->setPrice($price)
                        ->addKitItemLineItem(
                            (new OrderProductKitItemLineItem())
                                ->setKitItem($kitItem)
                                ->setProduct($product3)
                                ->setQuantity($kitItemLineItemQuantity)
                                ->setPrice($kitItemLineItemPrice)
                                ->setProductUnit($unitEach)
                        ),
                    (new OrderLineItem())->setProduct($product2)
                        ->setProductSku($product2->getSku())
                        ->setQuantity($quantity)
                        ->setProductUnit($unitItem)
                        ->setProductUnitCode($unitItem->getCode())
                        ->setFreeFormProduct('test2')
                        ->setPrice($price)
                        ->setPriceType(PriceTypeAwareInterface::PRICE_TYPE_BUNDLED)
                        ->setShipBy($now)
                        ->setFromExternalSource(true)
                        ->setComment('Comment')
                ])
            ],
            'data with non-existent property' => [
                'data' => [
                    [
                        'nonExistentProperty' => 'sampleValue',
                        'comment' => 'Comment'
                    ]
                ],
                'expected' => new ArrayCollection([(new OrderLineItem())->setComment('Comment')])
            ],
            'data with null property' => [
                'data' => [
                    [
                        'shipBy' => $now,
                        'comment' => null
                    ]
                ],
                'expected' => new ArrayCollection([(new OrderLineItem())->setShipBy($now)])
            ]
        ];
    }

    public function testReflectionClassInConvertCalled(): void
    {
        $reflectionMethod = $this->createMock(\ReflectionMethod::class);
        $reflectionMethod->expects(self::once())
            ->method('invoke');

        $reflectionClass = $this->createMock(EntityReflectionClass::class);
        $reflectionClass->expects(self::once())
            ->method('hasProperty')
            ->with('product')
            ->willReturn(true);
        $reflectionClass->expects(self::once())
            ->method('getMethod')
            ->with('setProduct')
            ->willReturn($reflectionMethod);

        ReflectionUtil::setPropertyValue(
            $this->checkoutLineItemsConverter,
            'reflectionClass',
            [OrderLineItem::class => $reflectionClass]
        );

        $this->checkoutLineItemsConverter->convert([['product' => (new Product())->setSku('product1')]]);
    }

    public function testReflectionClassInConvertNotCalled(): void
    {
        $reflectionClass = $this->createMock(EntityReflectionClass::class);
        $reflectionClass->expects(self::never())
            ->method('hasProperty');
        $reflectionClass->expects(self::never())
            ->method('getMethod');

        ReflectionUtil::setPropertyValue(
            $this->checkoutLineItemsConverter,
            'reflectionClass',
            [OrderLineItem::class => $reflectionClass]
        );

        $this->checkoutLineItemsConverter->convert([['product' => null]]);
    }

    public function testConvertWithDisabledReuse(): void
    {
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $data = [
            ['shipBy' => $now, 'comment' => null, 'checksum' => '123']
        ];
        $result1 = $this->checkoutLineItemsConverter->convert($data);
        $result2 = $this->checkoutLineItemsConverter->convert($data);

        self::assertNotSame($result1->first(), $result2->first());
    }

    public function testConvertWithEnabledReuse(): void
    {
        $this->checkoutLineItemsConverter->setReuseLineItems(true);
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $data = [
            ['shipBy' => $now, 'comment' => null, 'checksum' => '123']
        ];
        $result1 = $this->checkoutLineItemsConverter->convert($data);
        $result2 = $this->checkoutLineItemsConverter->convert($data);

        self::assertSame($result1->first(), $result2->first());
    }
}

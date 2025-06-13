<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Converter;

use Oro\Bundle\OrderBundle\ImportExport\Converter\OrderDiscountTypeConverter;
use PHPUnit\Framework\TestCase;

class OrderDiscountTypeConverterTest extends TestCase
{
    private OrderDiscountTypeConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new OrderDiscountTypeConverter();
    }

    public function testConvert(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'orderDiscountType' => 'test'
                ]
            ]
        ];

        $result = $this->converter->convert($item, 'sourceData');

        self::assertEquals(
            'oro_order_discount_item_type_test',
            $result['entity']['attributes']['orderDiscountType']
        );
    }

    public function testConvertWithoutOrderDiscountType(): void
    {
        $item = [
            'entity' => [
                'attributes' => []
            ]
        ];

        $result = $this->converter->convert($item, 'sourceData');

        self::assertEquals([], $result['entity']['attributes']);
    }

    public function testReverseConvert(): void
    {
        $item = [
            'type' => 'oro_order_discount_item_type_test'
        ];

        self::assertEquals(['type' => 'test'], $this->converter->reverseConvert($item, new \stdClass()));
    }

    public function testReverseConvertWithoutTypeData(): void
    {
        self::assertEquals([], $this->converter->reverseConvert([], new \stdClass()));
    }

    public function testConvertError(): void
    {
        self::assertEquals(
            'Allowed values: test1, test2.',
            $this->converter->convertError(
                'Allowed values: oro_order_discount_item_type_test1, oro_order_discount_item_type_test2.',
                'type'
            )
        );
    }
}

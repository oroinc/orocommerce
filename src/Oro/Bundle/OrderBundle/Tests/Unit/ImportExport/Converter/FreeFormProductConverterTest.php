<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\ImportExport\Converter;

use Oro\Bundle\OrderBundle\ImportExport\Converter\FreeFormProductConverter;
use PHPUnit\Framework\TestCase;

class FreeFormProductConverterTest extends TestCase
{
    private FreeFormProductConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->converter = new FreeFormProductConverter();
    }

    public function testConvertWhenTheProductWasFound(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'productName' => 'test product'
                ],
                'relationships' => [
                    'product' => ['data' => ['id' => 123]]
                ]
            ]
        ];

        $result = $this->converter->convert($item, 'sourceData');

        self::assertArrayNotHasKey('freeFormProduct', $result['entity']['attributes']);
        self::assertEquals('test product', $result['entity']['attributes']['productName']);
    }

    public function testConvertWhenTheProductWasNotFoundButWithProductName(): void
    {
        $item = [
            'entity' => [
                'attributes' => [
                    'productName' => 'test product'
                ]
            ]
        ];

        $result = $this->converter->convert($item, 'sourceData');

        self::assertArrayNotHasKey('productName', $result['entity']['attributes']);
        self::assertEquals('test product', $result['entity']['attributes']['freeFormProduct']);
    }
}

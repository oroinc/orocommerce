<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Model\Builder;

use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\ProductBundle\Model\Builder\QuickAddRowInputParser;
use Oro\Bundle\ProductBundle\Provider\ProductUnitsProvider;

class QuickAddRowInputParserTest extends \PHPUnit\Framework\TestCase
{
    /** @var NumberFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $numberFormatter;

    /** @var QuickAddRowInputParser */
    private $quickAddRowInputParser;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $productUnitsProvider = $this->createMock(ProductUnitsProvider::class);
        $productUnitsProvider->expects(self::any())
            ->method('getAvailableProductUnits')
            ->willReturn([
                'Element' => 'item',
                'Stunde' => 'hour',
                'Liter' => 'LITER',
                'Item2' => 'item1',
                'Item1' => 'item3'
            ]);

        $this->quickAddRowInputParser = new QuickAddRowInputParser(
            $productUnitsProvider,
            $this->numberFormatter
        );
    }

    private function expectsParseFormattedDecimal(): void
    {
        $this->numberFormatter->expects(self::once())
            ->method('parseFormattedDecimal')
            ->willReturnCallback(function ($value) {
                if (str_contains($value, ',')) {
                    return (float)str_replace(',', '.', $value);
                }
                if (!str_contains($value, '.')) {
                    return (float)$value;
                }

                return false;
            });
    }

    /**
     * @dataProvider rowFileDataProvider
     */
    public function testCreateFromFileLine(array $input, array $expected): void
    {
        $this->expectsParseFormattedDecimal();

        $index = 1;
        $result = $this->quickAddRowInputParser->createFromFileLine($input, $index);

        self::assertSame($index, $result->getIndex());
        self::assertSame($expected[0], $result->getSku());
        self::assertSame($expected[1], $result->getQuantity());
        self::assertSame($expected[2], $result->getUnit());
    }

    /**
     * @dataProvider rowFileDataProvider
     */
    public function testCreateFromPasteTextLine(array $input, array $expected): void
    {
        $this->expectsParseFormattedDecimal();

        $index = 1;
        $result = $this->quickAddRowInputParser->createFromCopyPasteTextLine($input, $index);

        self::assertSame($index, $result->getIndex());
        self::assertSame($expected[0], $result->getSku());
        self::assertSame($expected[1], $result->getQuantity());
        self::assertSame($expected[2], $result->getUnit());
        self::assertSame($expected[3] ?? null, $result->getOrganization());
    }

    public function rowFileDataProvider(): array
    {
        return [
            [
                'input' => [' SKU1  ', ' 4.5', 'item '],
                'expected' => ['SKU1', 4.5, 'item']
            ],
            [
                'input' => ['sku1', '.5', 'item'],
                'expected' => ['sku1', 0.0, 'item']
            ],
            [
                'input' => ['sku1', '   6 ', 'liter'],
                'expected' => ['sku1', 6.0, 'LITER']
            ],
            [
                'input' => ['sku1', '6'],
                'expected' => ['sku1', 6.0, null]
            ],
            [
                'input' => ['sku1', '4,5', 'Stunde '],
                'expected' => ['sku1', 4.5, 'hour']
            ],
            [
                'input' => ['sku1', '4,0', 'ELEMENT '],
                'expected' => ['sku1', 4.0, 'item']
            ],
            [
                'input' => ['sku1', '4,5', 'unknown'],
                'expected' => ['sku1', 4.5, 'unknown']
            ],
            [
                'input' => ['sku1', '4,5', 'ITEM1'],
                'expected' => ['sku1', 4.5, 'item3']
            ],
            [
                'input' => ['sku1', '4,5', 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1']
            ],
            [
                'input' => ['"sku1"', '4,5', 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1', null]
            ],
            [
                'input' => ['"sku1,"', '4,5', 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1', null]
            ],
            [
                'input' => ['"sku1,Org"', '4,5', 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1', 'Org']
            ],
            [
                'input' => ['"sku1, Org"', '4,5', 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1', 'Org']
            ],
        ];
    }

    /**
     * @dataProvider createFromRequestDataProvider
     */
    public function testCreateFromRequest(array $input, array $expected): void
    {
        $this->numberFormatter->expects(self::never())
            ->method('parseFormattedDecimal');

        $index = 1;
        $result = $this->quickAddRowInputParser->createFromRequest($input, $index);

        self::assertSame($index, $result->getIndex());
        self::assertSame($expected[0], $result->getSku());
        self::assertSame($expected[1], $result->getQuantity());
        self::assertSame($expected[2], $result->getUnit());
        self::assertSame($expected[3] ?? null, $result->getOrganization());
    }

    public function createFromRequestDataProvider(): array
    {
        return [
            [
                'input' => ['productSku' => ' SKU1  ', 'productQuantity' => ' 4.5', 'productUnit' => 'item '],
                'expected' => ['SKU1', 4.5, 'item']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '   6 ', 'productUnit' => 'liter'],
                'expected' => ['sku1', 6.0, 'LITER']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '6'],
                'expected' => ['sku1', 6.0, null]
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '4.5', 'productUnit' => 'Stunde '],
                'expected' => ['sku1', 4.5, 'hour']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '4.0', 'productUnit' => 'ELEMENT '],
                'expected' => ['sku1', 4.0, 'item']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '4.5', 'productUnit' => 'unknown'],
                'expected' => ['sku1', 4.5, 'unknown']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '4.5', 'productUnit' => 'ITEM1'],
                'expected' => ['sku1', 4.5, 'item3']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '4.5', 'productUnit' => 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '6', 'productOrganization' => null],
                'expected' => ['sku1', 6.0, null, null]
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '6', 'productOrganization' => ' '],
                'expected' => ['sku1', 6.0, null, null]
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '6', 'productOrganization' => 'Org'],
                'expected' => ['sku1', 6.0, null, 'Org']
            ],
            [
                'input' => ['productSku' => 'sku1', 'productQuantity' => '6', 'productOrganization' => ' Org '],
                'expected' => ['sku1', 6.0, null, 'Org']
            ],
        ];
    }

    /**
     * @dataProvider createFromArrayDataProvider
     */
    public function testCreateFromArray(array $input, array $expected): void
    {
        $this->numberFormatter->expects(self::never())
            ->method('parseFormattedDecimal');

        $index = 1;
        $result = $this->quickAddRowInputParser->createFromArray($input, $index);

        self::assertSame($index, $result->getIndex());
        self::assertSame($expected[0], $result->getSku());
        self::assertSame($expected[1], $result->getQuantity());
        self::assertSame($expected[2], $result->getUnit());
        self::assertSame($expected[3] ?? null, $result->getOrganization());
    }

    public function createFromArrayDataProvider(): array
    {
        return [
            [
                'input' => ['sku' => ' SKU1  ', 'quantity' => ' 4.5', 'unit' => 'item '],
                'expected' => ['SKU1', 4.5, 'item']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '   6 ', 'unit' => 'liter'],
                'expected' => ['sku1', 6.0, 'LITER']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '6'],
                'expected' => ['sku1', 6.0, null]
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'Stunde '],
                'expected' => ['sku1', 4.5, 'hour']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.0', 'unit' => 'ELEMENT '],
                'expected' => ['sku1', 4.0, 'item']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'unknown'],
                'expected' => ['sku1', 4.5, 'unknown']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM1'],
                'expected' => ['sku1', 4.5, 'item3']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2'],
                'expected' => ['sku1', 4.5, 'item1']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2', 'organization' => null],
                'expected' => ['sku1', 4.5, 'item1', null]
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2', 'organization' => ''],
                'expected' => ['sku1', 4.5, 'item1', null]
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2', 'organization' => ' '],
                'expected' => ['sku1', 4.5, 'item1', null]
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2', 'organization' => 'Org'],
                'expected' => ['sku1', 4.5, 'item1', 'Org']
            ],
            [
                'input' => ['sku' => 'sku1', 'quantity' => '4.5', 'unit' => 'ITEM2', 'organization' => ' Org '],
                'expected' => ['sku1', 4.5, 'item1', 'Org']
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\OptionsProvider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PayPalBundle\OptionsProvider\LineItemOptionsFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LineItemOptionsFormatterTest extends TestCase
{
    /** @var NumberFormatter|MockObject */
    private $numberFormatter;

    /** @var RoundingServiceInterface|MockObject */
    private $rounder;

    /** @var LineItemOptionsFormatter */
    private $lineItemOptionsFormatter;

    protected function setUp(): void
    {
        $this->numberFormatter = $this->createMock(NumberFormatter::class);
        $this->rounder = $this->createMock(RoundingServiceInterface::class);
        $this->lineItemOptionsFormatter = new LineItemOptionsFormatter($this->numberFormatter, $this->rounder);
    }

    /**
     * @dataProvider formatLineItemOptionsDataProvider
     */
    public function testFormatLineItemOptions(
        array $lineItemModelData,
        int $currencyRoundingPrecision,
        array $expected
    ): void {
        $lineItemModel = new LineItemOptionModel();
        $lineItemModel->setName($lineItemModelData['name']);
        $lineItemModel->setDescription($lineItemModelData['description']);
        $lineItemModel->setCost($lineItemModelData['cost']);
        $lineItemModel->setQty($lineItemModelData['qty']);
        $lineItemModel->setCurrency($lineItemModelData['currency']);
        $lineItemModel->setUnit($lineItemModelData['unit']);

        $this->numberFormatter
            ->expects($this->any())
            ->method('formatCurrency')
            ->willReturnCallback(function ($cost, $currency) {
                return sprintf('%s%s', $currency, round($cost, 2));
            });

        $this->rounder
            ->expects($this->any())
            ->method('round')
            ->willReturnCallback(function ($number, $precision) {
                return round($number, $precision);
            });

        $this->rounder->expects($this->any())
            ->method('getPrecision')
            ->willReturn($currencyRoundingPrecision);

        $formattedLineItemOptions = $this->lineItemOptionsFormatter->formatLineItemOptions([$lineItemModel]);
        $actual = reset($formattedLineItemOptions);

        $this->assertEquals($expected['name'], $actual->getName());
        $this->assertEquals($expected['description'], $actual->getDescription());
        $this->assertEqualsWithDelta($expected['cost'], round($actual->getCost(), 2), 1e-6);
        $this->assertEqualsWithDelta($expected['qty'], $actual->getQty(), 1e-6);
        $this->assertEquals($expected['currency'], $actual->getCurrency());
        $this->assertEquals($expected['unit'], $actual->getUnit());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formatLineItemOptionsDataProvider(): array
    {
        return [
            'Integer qty, cost precision 2' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Product Name',
                    'description' => 'Product Description',
                    'cost' => 123.45,
                    'qty' => 2,
                    'currency' => 'USD',
                    'unit' => 'item',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Product Name',
                    'description' => 'Product Description',
                    'cost' => 123.45,
                    'qty' => 2,
                    'currency' => 'USD',
                    'unit' => 'item',
                ],
            ],
            'Fractional qty, cost precision 2' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 56.23,
                    'qty' => 0.5,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 2,
                'expected' => [
                    'name' => 'PRSKU Long Product - EUR56.23x0.5 kg',
                    'description' => 'Product Description',
                    'cost' => 28.12,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Integer qty, cost precision 3' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 16.666,
                    'qty' => 2,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 3,
                'expected' => [
                    'name' => 'PRSKU Long Product N - EUR16.67x2 kg',
                    'description' => 'Product Description',
                    'cost' => 33.33,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Fractional qty, cost precision 3' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 13.336,
                    'qty' => 0.2,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 3,
                'expected' => [
                    'name' => 'PRSKU Long Product - EUR13.34x0.2 kg',
                    'description' => 'Product Description',
                    'cost' => 2.67,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
            'Fractional qty, system currency precision 1' => [
                'lineItemModelData' => [
                    'name' => 'PRSKU Long Product Name',
                    'description' => 'Product Description',
                    'cost' => 56.3,
                    'qty' => 0.5,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
                'currencyRoundingPrecision' => 1,
                'expected' => [
                    'name' => 'PRSKU Long Product  - EUR56.3x0.5 kg',
                    'description' => 'Product Description',
                    'cost' => 28.2,
                    'qty' => 1,
                    'currency' => 'EUR',
                    'unit' => 'kg',
                ],
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Calculator;

use Oro\Bundle\TaxBundle\Calculator\IncludedTaxCalculator;

class IncludedTaxCalculatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var IncludedTaxCalculator */
    private $calculator;

    protected function setUp(): void
    {
        $this->calculator = new IncludedTaxCalculator();
    }

    public function testAmountKey(): void
    {
        $this->assertIsString($this->calculator->getAmountKey());
    }

    /**
     * @dataProvider calculateDataProvider
     */
    public function testCalculate(array $expectedResult, string $taxableAmount, string $taxRate): void
    {
        $this->assertEquals(
            $expectedResult,
            array_values($this->calculator->calculate($taxableAmount, $taxRate)->getArrayCopy())
        );
    }

    /**
     * @link http://salestax.avalara.com/
     */
    public function calculateDataProvider(): array
    {
        return [
            // use cases
            'Finney County' => [['17.21', '15.986995', '1.223005'], '17.21', '0.0765'],
            'Fremont County' => [['59.04', '56.228571', '2.811429'], '59.04', '0.05'],
            'Tulare County' => [['14.41', '13.342593', '1.067407'], '14.41', '0.08'],
            'Mclean County' => [['35.88', '33.769412', '2.110588'], '35.88', '0.0625'],

            // edge cases
            [['15.98', '7.990000', '7.990000'], '15.98', '1'],
            [['15.98', '5.326667', '10.653333'], '15.98', '2'],
            [['15.98', '8.030151', '7.949849'], '15.98', '0.99'],
            [['15.98', '15.964036', '0.015964'], '15.98', '0.001'],
            [['15.98', '15.956066', '0.023934'], '15.98', '0.0015'],
            [['15.98', '13.316667', '2.663333'], '15.98', '-0.2'],
        ];
    }
}

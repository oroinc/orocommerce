<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\ResultElement;

class ResultElementTest extends \PHPUnit\Framework\TestCase
{
    private const INCLUDING_TAX = 1.2;
    private const EXCLUDING_TAX = 1;
    private const TAX_AMOUNT = 2;
    private const ADJUSTMENT = 3.4;

    public function testProperties(): void
    {
        $resultElement = $this->createResultElementModel();
        self::assertEquals(self::INCLUDING_TAX, $resultElement->getIncludingTax());
        self::assertEquals(self::EXCLUDING_TAX, $resultElement->getExcludingTax());
        self::assertEquals(self::TAX_AMOUNT, $resultElement->getTaxAmount());
        self::assertEquals(self::ADJUSTMENT, $resultElement->getAdjustment());
        self::assertFalse($resultElement->isDiscountsIncluded());

        self::assertCount(4, $resultElement);
        $expected = [
            'includingTax' => self::INCLUDING_TAX,
            'excludingTax' => self::EXCLUDING_TAX,
            'taxAmount' => self::TAX_AMOUNT,
            'adjustment' => self::ADJUSTMENT,
        ];

        foreach ($resultElement as $key => $value) {
            self::assertArrayHasKey($key, $expected);
            self::assertEquals($expected[$key], $value);
        }
    }

    /**
     * @dataProvider getDiscountsIncludedDataProvider
     */
    public function testDiscountsIncluded(bool $isDiscountsIncluded): void
    {
        $resultElement = $this->createResultElementModel();
        $resultElement->setDiscountsIncluded($isDiscountsIncluded);

        self::assertEquals($isDiscountsIncluded, $resultElement->isDiscountsIncluded());
    }

    public function getDiscountsIncludedDataProvider(): array
    {
        return [
            [false],
            [true],
        ];
    }

    protected function createResultElementModel(): ResultElement
    {
        return ResultElement::create(
            self::INCLUDING_TAX,
            self::EXCLUDING_TAX,
            self::TAX_AMOUNT,
            self::ADJUSTMENT
        );
    }

    public function testConstruct(): void
    {
        self::assertEquals(
            $this->createResultElementModel(),
            new ResultElement(
                [
                    ResultElement::EXCLUDING_TAX => self::EXCLUDING_TAX,
                    ResultElement::INCLUDING_TAX => self::INCLUDING_TAX,
                    ResultElement::TAX_AMOUNT => self::TAX_AMOUNT,
                    ResultElement::ADJUSTMENT => self::ADJUSTMENT,
                ]
            )
        );
    }

    public function testSetTransformsToNull(): void
    {
        $resultElement = $this->createResultElementModel();
        $resultElement->offsetSet('index', BigDecimal::of('2'));
        self::assertIsString($resultElement->getOffset('index'));
        self::assertEquals('2', $resultElement->getOffset('index'));
    }
}

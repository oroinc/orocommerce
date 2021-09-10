<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Model;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\ResultElement;

class ResultElementTest extends \PHPUnit\Framework\TestCase
{
    const INCLUDING_TAX = 1.2;
    const EXCLUDING_TAX = 1;
    const TAX_AMOUNT = 2;
    const ADJUSTMENT = 3.4;

    public function testProperties(): void
    {
        $resultElement = $this->createResultElementModel();
        self::assertEquals(static::INCLUDING_TAX, $resultElement->getIncludingTax());
        self::assertEquals(static::EXCLUDING_TAX, $resultElement->getExcludingTax());
        self::assertEquals(static::TAX_AMOUNT, $resultElement->getTaxAmount());
        self::assertEquals(static::ADJUSTMENT, $resultElement->getAdjustment());
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
            static::INCLUDING_TAX,
            static::EXCLUDING_TAX,
            static::TAX_AMOUNT,
            static::ADJUSTMENT
        );
    }

    public function testConstruct(): void
    {
        self::assertEquals(
            $this->createResultElementModel(),
            new ResultElement(
                [
                    ResultElement::EXCLUDING_TAX => static::EXCLUDING_TAX,
                    ResultElement::INCLUDING_TAX => static::INCLUDING_TAX,
                    ResultElement::TAX_AMOUNT => static::TAX_AMOUNT,
                    ResultElement::ADJUSTMENT => static::ADJUSTMENT,
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

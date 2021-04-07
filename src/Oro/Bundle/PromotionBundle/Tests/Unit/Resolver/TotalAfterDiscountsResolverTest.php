<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Oro\Bundle\PromotionBundle\Resolver\TotalAfterDiscountsResolver;
use Oro\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Resolver\RoundingResolver;

class TotalAfterDiscountsResolverTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $settingsProvider;

    /** @var TaxCalculatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $calculator;

    private TotalAfterDiscountsResolver $totalAfterDiscountsResolver;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);
        $this->calculator = $this->createMock(TaxCalculatorInterface::class);

        $this->totalAfterDiscountsResolver = new TotalAfterDiscountsResolver(
            $this->settingsProvider,
            new RoundingResolver(),
            $this->calculator
        );
    }

    public function testResolveCalculateAfterPromotionsDisabled(): void
    {
        $items = [
            [Result::ROW => ResultElement::create('24.1879', '19.99', '4.1979', '0.0021')],
        ];
        $taxable = new Taxable();
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationOnItem')
            ->willReturn(false);
        $this->settingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(false);

        $this->calculator->expects(self::never())
            ->method('calculate')
            ->withAnyParameters();

        $this->totalAfterDiscountsResolver->resolve($taxable);

        self::assertInstanceOf(Result::class, $taxable->getResult());
        self::assertInstanceOf(ResultElement::class, $taxable->getResult()->getTotal());
        self::assertEquals(
            ResultElement::create('24.1879', '19.99', '4.1979', '0.0021'),
            $taxable->getResult()->getTotal()
        );
    }

    public function testResolveTaxIsZero(): void
    {
        $resultElement = ResultElement::create(
            BigDecimal::zero(),
            BigDecimal::zero(),
            BigDecimal::zero(),
            BigDecimal::zero()
        );
        $items = [
            [Result::ROW => $resultElement],
        ];

        $taxable = new Taxable();
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationOnItem')
            ->willReturn(false);
        $this->settingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $this->calculator->expects(self::never())
            ->method('calculate')
            ->withAnyParameters();

        $this->totalAfterDiscountsResolver->resolve($taxable);

        self::assertInstanceOf(Result::class, $taxable->getResult());
        self::assertInstanceOf(ResultElement::class, $taxable->getResult()->getTotal());
        self::assertEquals($resultElement, $taxable->getResult()->getTotal());
    }

    public function testResolve(): void
    {
        $excludingTax = '19.99';
        $taxAmount = '4.1979';
        $items = [
            [Result::ROW => ResultElement::create('24.1879', $excludingTax, $taxAmount, '0.0021')],
        ];

        $taxable = new Taxable();
        $taxable->setAmount(20);
        foreach ($items as $item) {
            $itemTaxable = new Taxable();
            $itemTaxable->setResult(new Result($item));
            $taxable->addItem($itemTaxable);
        }

        $this->settingsProvider->expects(self::any())
            ->method('isStartCalculationOnItem')
            ->willReturn(false);
        $this->settingsProvider->expects(self::once())
            ->method('isCalculateAfterPromotionsEnabled')
            ->willReturn(true);

        $taxRate = BigDecimal::of($excludingTax)
            ->dividedBy(
                $taxAmount,
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            )
            ->dividedBy(
                100,
                TaxationSettingsProvider::CALCULATION_SCALE,
                RoundingMode::HALF_UP
            );

        $resultElement = ResultElement::create('21', '20', '0');

        $expectedResultElement = clone $resultElement;
        $expectedResultElement->setDiscountsIncluded(true)
            ->setAdjustment('0.00');

        $this->calculator->expects(self::once())
            ->method('calculate')
            ->with(BigDecimal::of(20), $taxRate)
            ->willReturn($resultElement);

        $this->totalAfterDiscountsResolver->resolve($taxable);

        self::assertInstanceOf(Result::class, $taxable->getResult());

        $total = $taxable->getResult()->getTotal();

        self::assertInstanceOf(ResultElement::class, $total);
        self::assertEquals($expectedResultElement, $total);
    }
}

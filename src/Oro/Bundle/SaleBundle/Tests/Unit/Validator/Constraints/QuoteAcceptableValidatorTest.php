<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteAcceptable;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteAcceptableValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuoteAcceptableValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): QuoteAcceptableValidator
    {
        return new QuoteAcceptableValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(Quote::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNullValue(): void
    {
        $this->validator->validate(null, new QuoteAcceptable());

        $this->assertNoViolation();
    }

    public function testValidateWithNonQuoteEntity(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::once())
            ->method('isAcceptable')
            ->willReturn(true);

        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects(self::once())
            ->method('getEntity')
            ->willReturn($quote);

        $this->validator->validate($checkoutSource, new QuoteAcceptable());

        $this->assertNoViolation();
    }

    public function testValidateWithNonQuoteEntityProhibitedByContstraintDefault(): void
    {
        $checkoutSource = $this->createMock(CheckoutSource::class);
        $checkoutSource->expects(self::once())
            ->method('getEntity')
            ->willReturn(null);

        $constraint = new QuoteAcceptable();
        $constraint->default = false;
        $this->validator->validate($checkoutSource, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%qid%', '0')
            ->setCode(QuoteAcceptable::CODE)
            ->assertRaised();
    }

    public function testValidateWithQuoteDemand(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::once())
            ->method('isAcceptable')
            ->willReturn(false);
        $quote->expects(self::once())
            ->method('getQid')
            ->willReturn('12345');

        $quoteDemand = $this->createMock(QuoteDemand::class);
        $quoteDemand->expects(self::once())
            ->method('getQuote')
            ->willReturn($quote);

        $constraint = new QuoteAcceptable();
        $this->validator->validate($quoteDemand, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%qid%', '12345')
            ->setCode(QuoteAcceptable::CODE)
            ->assertRaised();
    }

    public function testValidateWithAcceptableQuote(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::once())
            ->method('isAcceptable')
            ->willReturn(true);

        $this->validator->validate($quote, new QuoteAcceptable());

        $this->assertNoViolation();
    }

    public function testValidateWithNonAcceptableQuote(): void
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::once())
            ->method('isAcceptable')
            ->willReturn(false);
        $quote->expects(self::once())
            ->method('getQid')
            ->willReturn('12345');

        $constraint = new QuoteAcceptable();
        $this->validator->validate($quote, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%qid%', '12345')
            ->setCode(QuoteAcceptable::CODE)
            ->assertRaised();
    }
}

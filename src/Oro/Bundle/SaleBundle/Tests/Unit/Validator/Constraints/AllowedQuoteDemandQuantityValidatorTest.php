<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductDemand;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Validator\Constraints\AllowedQuoteDemandQuantity;
use Oro\Bundle\SaleBundle\Validator\Constraints\AllowedQuoteDemandQuantityValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AllowedQuoteDemandQuantityValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): AllowedQuoteDemandQuantityValidator
    {
        return new AllowedQuoteDemandQuantityValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(QuoteProductDemand::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNonQuoteProductDemandEntity(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new AllowedQuoteDemandQuantity());
    }

    /**
     * @dataProvider validateAllowIncrementsProvider
     */
    public function testValidateAllowIncrements(QuoteProductDemand $value, bool $valid): void
    {
        $constraint = new AllowedQuoteDemandQuantity();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->lessQuantityMessage)
                ->atPath('property.path.quantity')
                ->assertRaised();
        }
    }

    public function validateAllowIncrementsProvider(): array
    {
        return [
            'product quantity equals to offer quantity' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1.0, true)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, int' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1, true)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, float, int' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1, true)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, int, float' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1.0, true)),
                'valid' => true
            ],
            'product quantity greater than offer quantity' => [
                'value' => $this->getQuoteProductDemand(1.1, $this->getQuoteProductOffer(1.0, true)),
                'valid' => true
            ],
            'product quantity greater than offer quantity, int' => [
                'value' => $this->getQuoteProductDemand(2, $this->getQuoteProductOffer(1, true)),
                'valid' => true
            ],
            'product quantity greater than offer quantity, float, int' => [
                'value' => $this->getQuoteProductDemand(1.1, $this->getQuoteProductOffer(1, true)),
                'valid' => true
            ],
            'product quantity greater than offer quantity, int, float' => [
                'value' => $this->getQuoteProductDemand(2, $this->getQuoteProductOffer(1.0, true)),
                'valid' => true
            ],
            'product quantity less than offer quantity' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1.1, true)),
                'valid' => false
            ],
            'product quantity less than offer quantity, int' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(2, true)),
                'valid' => false
            ],
            'product quantity less than offer quantity, float, int' => [
                'value' => $this->getQuoteProductDemand(0.9, $this->getQuoteProductOffer(1, true)),
                'valid' => false
            ],
            'product quantity less than offer quantity, int, float' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1.1, true)),
                'valid' => false
            ],
        ];
    }

    /**
     * @dataProvider validateNotAllowIncrementsProvider
     */
    public function testValidateNotAllowIncrements(QuoteProductDemand $value, bool $valid): void
    {
        $constraint = new AllowedQuoteDemandQuantity();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->notEqualQuantityMessage)
                ->atPath('property.path.quantity')
                ->assertRaised();
        }
    }

    public function validateNotAllowIncrementsProvider(): array
    {
        return [
            'product quantity equals to offer quantity' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1.0)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, int' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, float, int' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1)),
                'valid' => true
            ],
            'product quantity equals to offer quantity, int, float' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1.0)),
                'valid' => true
            ],
            'product quantity not equals to offer quantity' => [
                'value' => $this->getQuoteProductDemand(1.0, $this->getQuoteProductOffer(1.1)),
                'valid' => false
            ],
            'product quantity not equals to offer quantity, int' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(2)),
                'valid' => false
            ],
            'product quantity not equals to offer quantity, float, int' => [
                'value' => $this->getQuoteProductDemand(1.1, $this->getQuoteProductOffer(1)),
                'valid' => false
            ],
            'product quantity not equals to offer quantity, int, float' => [
                'value' => $this->getQuoteProductDemand(1, $this->getQuoteProductOffer(1.1)),
                'valid' => false
            ],
        ];
    }

    private function getQuoteProductDemand(float|int|null $quantity, QuoteProductOffer $offer): QuoteProductDemand
    {
        return new QuoteProductDemand(
            $this->createMock(QuoteDemand::class),
            $offer,
            $quantity
        );
    }

    private function getQuoteProductOffer(float|int|null $quantity, bool $allowIncrements = false): QuoteProductOffer
    {
        $offer = new QuoteProductOffer();
        $offer->setQuantity($quantity);
        $offer->setAllowIncrements($allowIncrements);

        return $offer;
    }
}

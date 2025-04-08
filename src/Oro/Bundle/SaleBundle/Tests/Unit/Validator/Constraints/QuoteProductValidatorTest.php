<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteProduct as QuoteProductConstraint;
use Oro\Bundle\SaleBundle\Validator\Constraints\QuoteProductValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuoteProductValidatorTest extends ConstraintValidatorTestCase
{
    #[\Override]
    protected function createValidator(): QuoteProductValidator
    {
        return new QuoteProductValidator();
    }

    public function testUnexpectedConstraint(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(QuoteProduct::class), $this->createMock(Constraint::class));
    }

    public function testValidateWithNonQuoteProductEntity(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate(new \stdClass(), new QuoteProductConstraint());
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(QuoteProduct $value, bool $valid, string $fieldPath = 'product'): void
    {
        $constraint = new QuoteProductConstraint();
        $this->validator->validate($value, $constraint);

        if ($valid) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($constraint->message)
                ->atPath('property.path.' . $fieldPath)
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            'empty product & empty free form' => [
                'value' => new QuoteProduct(),
                'valid' => false
            ],
            'empty product replacement & empty free form replacement' => [
                'value' => $this->getQuoteProduct(
                    new Product(),
                    null,
                    QuoteProduct::TYPE_NOT_AVAILABLE
                ),
                'valid' => false,
                'fieldPath' => 'productReplacement'
            ],
            'empty product & filled free form' => [
                'value' => $this->getQuoteProduct(
                    new Product(),
                    null,
                    QuoteProduct::TYPE_OFFER,
                    'free form product'
                ),
                'valid' => true
            ],
            'empty product replacement & filled free form replacement' => [
                'value' => $this->getQuoteProduct(
                    null,
                    new Product(),
                    QuoteProduct::TYPE_NOT_AVAILABLE,
                    '',
                    'free form product'
                ),
                'valid' => true,
                'fieldPath' => 'product'
            ],
            'filled product' => [
                'value' => $this->getQuoteProduct(
                    new Product(),
                    null,
                    QuoteProduct::TYPE_OFFER,
                    'free form product'
                ),
                'valid' => true,
                'fieldPath' => 'product'
            ],
            'filled product replacement' => [
                'value' => $this->getQuoteProduct(
                    null,
                    new Product(),
                    QuoteProduct::TYPE_NOT_AVAILABLE,
                    '',
                    'free form product'
                ),
                'valid' => true,
                'fieldPath' => 'product'
            ],
        ];
    }

    private function getQuoteProduct(
        ?Product $product = null,
        ?Product $replacement = null,
        int $type = QuoteProduct::TYPE_OFFER,
        string $freeFormProduct = '',
        string $freeFormProductReplacement = ''
    ): QuoteProduct {
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setType($type);
        $quoteProduct->setProduct($product);
        $quoteProduct->setProductReplacement($replacement);
        $quoteProduct->setFreeFormProduct($freeFormProduct);
        $quoteProduct->setFreeFormProductReplacement($freeFormProductReplacement);

        return $quoteProduct;
    }
}

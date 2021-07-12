<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class QuoteProductValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new Constraints\QuoteProductValidator();
    }

    public function testConfiguration()
    {
        $constraint = new Constraints\QuoteProduct();
        self::assertEquals('oro_sale.validator.quote_product', $constraint->validatedBy());
        self::assertEquals([Constraint::CLASS_CONSTRAINT], $constraint->getTargets());
    }

    public function testNotQuoteProduct()
    {
        $this->expectException(UnexpectedTypeException::class);

        $constraint = new Constraints\QuoteProduct();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($data, bool $valid, string $fieldPath = 'product')
    {
        $constraint = new Constraints\QuoteProduct();
        $this->validator->validate($data, $constraint);

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
        $product = new Product();

        $item1 = new QuoteProduct();
        $item2 = $this->getQuoteProduct($product, null, QuoteProduct::TYPE_NOT_AVAILABLE);
        $item3 = $this->getQuoteProduct($product, null, QuoteProduct::TYPE_OFFER, 'free form product');
        $item4 = $this->getQuoteProduct(null, $product, QuoteProduct::TYPE_NOT_AVAILABLE, '', 'free form product');
        $item5 = $this->getQuoteProduct($product, null, QuoteProduct::TYPE_OFFER, 'free form product');
        $item6 = $this->getQuoteProduct(null, $product, QuoteProduct::TYPE_NOT_AVAILABLE, '', 'free form product');

        return [
            'empty product & empty free form' => [
                'data'      => $item1,
                'valid'     => false,
            ],
            'empty product replacement & empty free form replacement' => [
                'data'      => $item2,
                'valid'     => false,
                'fieldPath' => 'productReplacement',
            ],
            'empty product & filled free form' => [
                'data'      => $item3,
                'valid'     => true,
            ],
            'empty product replacement & filled free form replacement' => [
                'data'      => $item4,
                'valid'     => true,
                'fieldPath' => 'product',
            ],
            'filled product' => [
                'data'      => $item5,
                'valid'     => true,
                'fieldPath' => 'product',
            ],
            'filled product replacement' => [
                'data'      => $item6,
                'valid'     => true,
                'fieldPath' => 'product',
            ],
        ];
    }

    private function getQuoteProduct(
        Product $product = null,
        Product $replacement = null,
        int $type = QuoteProduct::TYPE_OFFER,
        string $freeFormProduct = '',
        string $freeFormProductReplacement = ''
    ): QuoteProduct {
        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setType($type)
            ->setProduct($product)
            ->setProductReplacement($replacement)
            ->setFreeFormProduct($freeFormProduct)
            ->setFreeFormProductReplacement($freeFormProductReplacement);

        return $quoteProduct;
    }
}

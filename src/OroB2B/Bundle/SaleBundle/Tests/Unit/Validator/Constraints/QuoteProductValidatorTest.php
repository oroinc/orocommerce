<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Validator\Constraints;

class QuoteProductValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraints\QuoteProduct
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var Constraints\QuoteProductValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new Constraints\QuoteProduct();
        $this->validator    = new Constraints\QuoteProductValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        static::assertEquals(
            'orob2b_sale.validator.quote_product',
            $this->constraint->validatedBy()
        );

        static::assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotQuoteProduct()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @param mixed $data
     * @param boolean $valid
     * @param string $fieldPath
     * @dataProvider validateProvider
     */
    public function testValidate($data, $valid, $fieldPath = 'product')
    {
        $this->context
            ->expects($valid ? static::never() : static::once())
            ->method('addViolationAt')
            ->with($fieldPath, $this->constraint->message)
        ;
        $this->validator->validate($data, $this->constraint);
    }
    /**
     * @return array
     */
    public function validateProvider()
    {
        $product = new Product();

        $item1 = new QuoteProduct();
        $item2 = $this->createQuoteProduct($product, null, QuoteProduct::TYPE_NOT_AVAILABLE);
        $item3 = $this->createQuoteProduct($product, null, QuoteProduct::TYPE_OFFER, 'free form product');
        $item4 = $this->createQuoteProduct(null, $product, QuoteProduct::TYPE_NOT_AVAILABLE, '', 'free form product');
        $item5 = $this->createQuoteProduct($product, null, QuoteProduct::TYPE_OFFER, 'free form product');
        $item6 = $this->createQuoteProduct(null, $product, QuoteProduct::TYPE_NOT_AVAILABLE, '', 'free form product');

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

    /**
     * @param Product $product
     * @param Product $replacement
     * @param int $type
     * @param string $freeFormProduct
     * @param string $freeFormProductReplacement
     * @return QuoteProduct
     */
    protected function createQuoteProduct(
        Product $product = null,
        Product $replacement = null,
        $type = QuoteProduct::TYPE_OFFER,
        $freeFormProduct = '',
        $freeFormProductReplacement = ''
    ) {
        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setType($type)
            ->setProduct($product)
            ->setProductReplacement($replacement)
            ->setFreeFormProduct($freeFormProduct)
            ->setFreeFormProductReplacement($freeFormProductReplacement)
        ;

        return $quoteProduct;
    }
}

<?php

namespace OroB2B\Bundle\SaleBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use OroB2B\Bundle\SaleBundle\Validator\Constraints;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProduct;
use OroB2B\Bundle\SaleBundle\Entity\QuoteProductOffer;

class QuoteProductOfferValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraints\QuoteProductOffer
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var Constraints\QuoteProductOfferValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new Constraints\QuoteProductOffer();
        $this->validator    = new Constraints\QuoteProductOfferValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'orob2b_sale.validator.quote_product_unit',
            $this->constraint->validatedBy()
        );

        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotQuoteProductOffer()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @param mixed $data
     * @param boolean $valid
     * @dataProvider validateProvider
     */
    public function testValidate($data, $valid)
    {
        $this->context
            ->expects($valid ? $this->never() : $this->once())
            ->method('addViolationAt')
            ->with('productUnit', $this->constraint->message)
        ;
        $this->validator->validate($data, $this->constraint);
    }
    /**
     * @return array
     */
    public function validateProvider()
    {
        $product = (new Product())
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit1')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit2')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit3')))
        ;
        $replacement = (new Product())
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item1')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item2')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('item3')))
        ;
        $item1 = new QuoteProductOffer();

        $item2 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct())
        ;
        $item3 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct(new Product()))
        ;
        $item4 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct($product));

        $item5 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct($product))
            ->setProductUnit((new ProductUnit())->setCode('unit5'))
        ;
        $item6 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct($product))
            ->setProductUnit((new ProductUnit())->setCode('unit1'))
        ;
        $item7 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct($product, $replacement, QuoteProduct::TYPE_NOT_AVAILABLE))
            ->setProductUnit((new ProductUnit())->setCode('unit1'))
        ;
        $item8 = (new QuoteProductOffer())
            ->setQuoteProduct($this->createQuoteProduct($product, $replacement, QuoteProduct::TYPE_NOT_AVAILABLE))
            ->setProductUnit((new ProductUnit())->setCode('item1'))
        ;
        return [
            'offer: empty request product' => [
                'data'  => $item1,
                'valid' => false,
            ],
            'offer: empty product' => [
                'data'  => $item2,
                'valid' => false,
            ],
            'offer: empty allowed units' => [
                'data'  => $item3,
                'valid' => false,
            ],
            'offer: empty product unit' => [
                'data'  => $item4,
                'valid' => false,
            ],
            'offer: ivalid unit code' => [
                'data'  => $item5,
                'valid' => false,
            ],
            'offer: valid unit code' => [
                'data'  => $item6,
                'valid' => true,
            ],
            'replacement: invalid unit code' => [
                'data'  => $item7,
                'valid' => false,
            ],
            'replacement: valid unit code' => [
                'data'  => $item8,
                'valid' => true,
            ],
        ];
    }

    /**
     * @param Product $product
     * @param Product $replacement
     * @param int $type
     * @return QuoteProduct
     */
    protected function createQuoteProduct(
        Product $product = null,
        Product $replacement = null,
        $type = QuoteProduct::TYPE_OFFER
    ) {
        $quoteProduct = new QuoteProduct();
        $quoteProduct
            ->setType($type)
            ->setProduct($product)
            ->setProductReplacement($replacement)
        ;

        return $quoteProduct;
    }
}

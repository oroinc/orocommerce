<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductUnitHolder;
use OroB2B\Bundle\ProductBundle\Validator\Constraints\ProductUnitHolderValidator;

use OroB2B\Bundle\ProductBundle\Validator\Constraints;

class ProductUnitHolderValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductUnitHolder
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var ProductUnitHolderValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new ProductUnitHolder();
        $this->validator    = new ProductUnitHolderValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        static::assertEquals(
            'orob2b_product.validator.product_unit_holder',
            $this->constraint->validatedBy()
        );

        static::assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotRequestProductItem()
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
            ->expects($valid ? static::never() : static::once())
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

        $item1 = $this->createProductUnitHolder();

        $item2 = $this->createProductUnitHolder(null, $this->createProductHolder());

        $item3 = $this->createProductUnitHolder(null, $this->createProductHolder(new Product()));

        $item4 = $this->createProductUnitHolder(null, $this->createProductHolder($product));

        $item5 = $this->createProductUnitHolder(
            (new ProductUnit())->setCode('unit5'),
            $this->createProductHolder($product)
        );

        $item6 = $this->createProductUnitHolder(
            (new ProductUnit())->setCode('unit1'),
            $this->createProductHolder($product)
        );
        return [
            'empty holder product' => [
                'data'  => $item1,
                'valid' => false,
            ],
            'empty product' => [
                'data'  => $item2,
                'valid' => false,
            ],
            'empty allowed units' => [
                'data'  => $item3,
                'valid' => false,
            ],
            'empty product unit' => [
                'data'  => $item4,
                'valid' => false,
            ],
            'invalid unit code' => [
                'data'  => $item5,
                'valid' => false,
            ],
            'valid unit code' => [
                'data'  => $item6,
                'valid' => true,
            ],
        ];
    }

    /**
     * @param ProductUnit $productUnit
     * @param ProductHolderInterface $productHolder
     * @return ProductUnitHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductUnitHolder(
        ProductUnit $productUnit = null,
        ProductHolderInterface $productHolder = null
    ) {
        /* @var $productUmitHolder \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface */
        $productUnitHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface');

        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnit')
            ->willReturn($productUnit)
        ;
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductHolder')
            ->willReturn($productHolder)
        ;

        return $productUnitHolder;
    }

    /**
      * @param Product $product
      * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
      */
    protected function createProductHolder(Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');

        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product)
        ;
        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($product ? $product->getSku() : null)
        ;

        return $productHolder;
    }
}

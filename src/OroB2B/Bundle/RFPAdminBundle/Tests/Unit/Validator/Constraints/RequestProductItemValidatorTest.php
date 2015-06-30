<?php

namespace OroB2B\Bundle\RFPAdminBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ExecutionContextInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\RFPAdminBundle\Validator\Constraints;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProduct;
use OroB2B\Bundle\RFPAdminBundle\Entity\RequestProductItem;

class RequestProductItemValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Constraints\RequestProductItem
     */
    protected $constraint;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface
     */
    protected $context;

    /**
     * @var Constraints\RequestProductItemValidator
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context      = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->constraint   = new Constraints\RequestProductItem();
        $this->validator    = new Constraints\RequestProductItemValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals([Constraint::CLASS_CONSTRAINT], $this->constraint->getTargets());
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
     * @dataProvider allowedUnitsProvider
     */
    public function testAllowedUnits($data, $valid)
    {
        if ($valid) {
            $this->context
                ->expects($this->never())
                ->method('addViolationAt')
            ;
        } else {
            $this->context
                ->expects($this->once())
                ->method('addViolationAt')
                ->with('productUnit', $this->constraint->message)
            ;
        }

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @return array
     */
    public function allowedUnitsProvider()
    {
        $product = (new Product())
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit1')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit2')))
            ->addUnitPrecision((new ProductUnitPrecision())->setUnit((new ProductUnit())->setCode('unit3')))
        ;

        $item1 = (new RequestProductItem())
            ->setRequestProduct(new RequestProduct())
            ->setProductUnit((new ProductUnit())->setCode('unit1'))
        ;

        $item2 = (new RequestProductItem())
            ->setRequestProduct((new RequestProduct())->setProduct($product))
            ->setProductUnit((new ProductUnit())->setCode('unit1'))
        ;

        $item3 = (new RequestProductItem())
            ->setRequestProduct((new RequestProduct())->setProduct($product))
            ->setProductUnit((new ProductUnit())->setCode('unit5'))
        ;

        return [
            'empty product' => [
                'data'  => $item1,
                'valid' => false,
            ],
            'valid unit code' => [
                'data'  => $item2,
                'valid' => true,
            ],
            'ivalid unit code' => [
                'data'  => $item3,
                'valid' => false,
            ],
        ];
    }
}

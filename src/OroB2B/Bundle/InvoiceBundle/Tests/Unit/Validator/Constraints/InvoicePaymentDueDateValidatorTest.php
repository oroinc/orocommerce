<?php

namespace OroB2B\Bundle\InvoiceBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\InvoiceBundle\Validator\Constraints\InvoicePaymentDueDate;
use OroB2B\Bundle\InvoiceBundle\Validator\Constraints\InvoicePaymentDueDateValidator;
use Symfony\Component\Validator\Context\ExecutionContext;

class InvoicePaymentDueDateValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  InvoicePaymentDueDate
     */
    protected $constraint;

    /**
     * @var  InvoicePaymentDueDateValidator
     */
    protected $validator;

    public function setUp()
    {
        parent::setUp();
        $this->constraint = new InvoicePaymentDueDate();
        $this->validator = new InvoicePaymentDueDateValidator();
    }

    public function testValidationOnValid()
    {
//        $this->validator->initialize($this->getContextMock());
//        $this->validator->validate($this->createConfigs(2), $this->constraint);
    }

    public function testValidationOnInvalid()
    {
//        $builder = $this->getBuilderMock();
//
//        $builder->expects($this->once())
//            ->method('atPath')
//            ->with('[2].priceList')
//            ->willReturn($builder)
//        ;
//
//        $context = $this->getContextMock();
//        $context->expects($this->once())
//            ->method('buildViolation')
//            ->with($this->equalTo($this->constraint->message), [])
//            ->will($this->returnValue($builder))
//        ;
//
//        $this->validator->initialize($context);
//
//        $value = array_merge($this->createConfigs(2), $this->createConfigs(1));
//        $this->validator->validate($value, $this->constraint);
    }

    public function testValidationOnInvalidArrayValue()
    {
//        $builder = $this->getBuilderMock();
//
//        $builder->expects($this->once())
//            ->method('atPath')
//            ->with('[2][priceList]')
//            ->willReturn($builder)
//        ;
//
//        $context = $this->getContextMock();
//        $context->expects($this->once())
//            ->method('buildViolation')
//            ->with($this->equalTo($this->constraint->message), [])
//            ->will($this->returnValue($builder))
//        ;
//
//        $this->validator->initialize($context);
//
//        $value = array_map(function ($item) {
//            /** @var PriceListConfig $item */
//            return ['priceList' => $item->getPriceList(), 'priority' => $item->getPriority()];
//        }, array_merge($this->createConfigs(2), $this->createConfigs(1)));
//        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext $context
     */
    protected function getContextMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Context\ExecutionContext')
            ->disableOriginalConstructor()
            ->setMethods(['buildViolation'])
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getBuilderMock()
    {
        return $this->getMockBuilder('Symfony\Component\Validator\Violation\ConstraintViolationBuilder')
            ->disableOriginalConstructor()
            ->setMethods(['addViolation', 'atPath'])
            ->getMock()
            ;
    }

}

<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Validator\Constraints\InvoicePaymentDueDate;
use Oro\Bundle\InvoiceBundle\Validator\Constraints\InvoicePaymentDueDateValidator;

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

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->constraint = new InvoicePaymentDueDate();
        $this->validator = new InvoicePaymentDueDateValidator();
    }

    public function testValidationOnValid()
    {
        $invoice = new Invoice();
        $invoice->setPaymentDueDate(new \DateTime('+1 days'))
            ->setInvoiceDate(new \DateTime());
        $this->validator->initialize($this->getContextMock());
        $this->validator->validate($invoice, $this->constraint);
    }

    public function testValidationOnInvalid()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with(InvoicePaymentDueDateValidator::VIOLATION_PATH)
            ->willReturn($builder);

        $context = $this->getContextMock();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($this->constraint->message), [])
            ->will($this->returnValue($builder));

        $this->validator->initialize($context);
        $invoice = new Invoice();
        $invoice->setPaymentDueDate(new \DateTime('yesterday'))
            ->setInvoiceDate(new \DateTime());
        $this->validator->validate($invoice, $this->constraint);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExecutionContext
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
            ->getMock();
    }
}

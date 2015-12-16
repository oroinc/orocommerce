<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use OroB2B\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;
use OroB2B\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator;

class ZipCodeFieldsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var ZipCodeFieldsValidator
     */
    protected $validator;

    /**
     * @var ZipCodeFields
     */
    protected $constraint;

    /**
     * @var string
     */
    protected $expectedPropertyPath = 'property_path.zipCodes';

    public function setUp()
    {
        $this->constraint = new ZipCodeFields();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->context
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn('property_path');

        $this->validator = new ZipCodeFieldsValidator();
        $this->validator->initialize($this->context);
    }

    public function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testValidateWithSingleAndRange()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with($this->expectedPropertyPath, $this->constraint->onlyOneTypeMessage);

        $zipCode = ZipCodeTestHelper::getSingleValueZipCode('0100')
            ->setZipRangeStart('0500')
            ->setZipRangeEnd('0600');

        $this->validator->validate($zipCode, $this->constraint);
    }

    public function testValidateWithOnlyOneFieldOfRange()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with($this->expectedPropertyPath, $this->constraint->rangeBothFieldMessage);

        $zipCode = ZipCodeTestHelper::getRangeZipCode('0100', null);

        $this->validator->validate($zipCode, $this->constraint);
    }
}

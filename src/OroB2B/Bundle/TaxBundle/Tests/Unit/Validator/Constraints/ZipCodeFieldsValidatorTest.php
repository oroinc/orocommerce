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

    protected function setUp()
    {
        $this->constraint = new ZipCodeFields();
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');

        $this->validator = new ZipCodeFieldsValidator();
        $this->validator->initialize($this->context);
    }

    protected function tearDown()
    {
        unset($this->constraint, $this->context, $this->validator);
    }

    public function testValidateWithSingleAndRange()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with('zipCode', $this->constraint->onlyOneTypeMessage);

        $zipCode = ZipCodeTestHelper::getSingleValueZipCode('0100')
            ->setZipRangeStart('0500')
            ->setZipRangeEnd('0600');

        $this->validator->validate($zipCode, $this->constraint);
    }

    public function testValidateWithRangeStartOnlyField()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with('zipRangeEnd', $this->constraint->rangeShouldHaveBothFieldMessage);

        $zipCode = ZipCodeTestHelper::getRangeZipCode('0100', null);

        $this->validator->validate($zipCode, $this->constraint);
    }

    public function testValidateWithRangeEndOnlyField()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with('zipRangeStart', $this->constraint->rangeShouldHaveBothFieldMessage);

        $zipCode = ZipCodeTestHelper::getRangeZipCode(null, '0100');

        $this->validator->validate($zipCode, $this->constraint);
    }
}

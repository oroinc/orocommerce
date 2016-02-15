<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\TaxBundle\Entity\ZipCode;
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

    /**
     * @dataProvider validateProvider
     *
     * @param ZipCode $zipCode
     * @param array $violationContext
     */
    public function testValidate(ZipCode $zipCode, $violationContext)
    {
        if (0 === count($violationContext)) {
            $this->context->expects($this->never())
                ->method('addViolationAt');
        } else {
            $this->context->expects($this->once())
                ->method('addViolationAt')
                ->with($violationContext[0], $this->constraint->{$violationContext[1]});
        }

        $this->validator->validate($zipCode, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'single and range' => [
                ZipCodeTestHelper::getSingleValueZipCode('0100')
                    ->setZipRangeStart('0500')
                    ->setZipRangeEnd('0600'),
                [
                    'zipCode',
                    'onlyOneTypeMessage'
                ]
            ],
            'range start only' => [
                ZipCodeTestHelper::getRangeZipCode('0100', null),
                [
                    'zipRangeEnd',
                    'rangeShouldHaveBothFieldMessage'
                ]
            ],
            'range end only' => [
                ZipCodeTestHelper::getRangeZipCode(null, '0100'),
                [
                    'zipRangeStart',
                    'rangeShouldHaveBothFieldMessage'
                ]
            ],
            'single value' => [
                ZipCodeTestHelper::getSingleValueZipCode('0100'),
                null
            ],
            'range value' => [
                ZipCodeTestHelper::getRangeZipCode('0100', '0200'),
                null
            ],
            'range with non-numeric range (both non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('0A35DA', '0A35CA'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported'
                ]
            ],
            'range with non-numeric range (range start non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('0A35DA', '01234'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported'
                ]
            ],
            'range with non-numeric range (range end non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('01234', '0A35CA'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported'
                ]
            ],
        ];
    }

    public function testValidateWithZipEmpty()
    {
        $this->context->expects($this->once())
            ->method('addViolationAt')
            ->with('zipRangeStart', $this->constraint->zipCodeCanNotBeEmpty);

        $this->validator->validate(new ZipCode(), $this->constraint);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "OroB2B\Bundle\TaxBundle\Entity\ZipCode", "stdClass" given
     */
    public function testValidateWrongEntity()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }
}

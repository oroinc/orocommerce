<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator;

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
     * @param array $violationCounts
     */
    public function testValidate(ZipCode $zipCode, array $violationContext = [], array $violationCounts = [])
    {
        $validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');

        $validator->expects($this->any())->method('validate')->willReturnCallback(
            function ($value) use ($violationCounts) {
                $list = $this->getMock('Symfony\Component\Validator\ConstraintViolationListInterface');

                if (!array_key_exists($value, $violationCounts)) {
                    $list->expects($this->once())->method('count')->willReturn(0);

                    return $list;
                }

                $list->expects($this->once())->method('count')->willReturn($violationCounts[$value]);

                return $list;
            }
        );

        $this->context->expects($this->any())->method('getValidator')->willReturn($validator);

        if (0 === count($violationContext)) {
            $this->context->expects($this->never())->method('buildViolation');
        } else {
            $builder = $this->getMock('\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');

            $this->context->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->{$violationContext[1]})
                ->willReturn($builder);

            $builder->expects($this->once())->method('atPath')->with($violationContext[0])->willReturn($builder);
            $builder->expects($this->once())->method('addViolation');
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
                    'onlyOneTypeMessage',
                ],
            ],
            'range start only' => [
                ZipCodeTestHelper::getRangeZipCode('0100', null),
                [
                    'zipRangeEnd',
                    'rangeShouldHaveBothFieldMessage',
                ],
            ],
            'range end only' => [
                ZipCodeTestHelper::getRangeZipCode(null, '0100'),
                [
                    'zipRangeStart',
                    'rangeShouldHaveBothFieldMessage',
                ],
            ],
            'single value' => [
                ZipCodeTestHelper::getSingleValueZipCode('0100'),
            ],
            'range value' => [
                ZipCodeTestHelper::getRangeZipCode('0100', '0200'),
            ],
            'range with non-numeric range (both non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('0A35DA', '0A35CA'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported',
                ],
                ['0A35DA' => 1, '0A35CA' => 1],
            ],
            'range with non-numeric range (range start non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('0A35DA', '01234'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported',
                ],
                ['0A35DA' => 1, '01234' => 0],
            ],
            'range with non-numeric range (range end non-numeric)' => [
                ZipCodeTestHelper::getRangeZipCode('01234', '0A35CA'),
                [
                    'zipRangeStart',
                    'onlyNumericRangesSupported',
                ],
                ['01234' => 0, '0A35CA' => 1],
            ],
        ];
    }

    public function testValidateWithZipEmpty()
    {
        $builder = $this->getMock('\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $builder->expects($this->once())->method('atPath')->with('zipRangeStart')->willReturn($builder);
        $builder->expects($this->once())->method('addViolation');

        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($this->constraint->zipCodeCanNotBeEmpty)
            ->willReturn($builder);

        $this->validator->validate(new ZipCode(), $this->constraint);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\TaxBundle\Entity\ZipCode", "stdClass" given
     */
    public function testValidateWrongEntity()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }
}

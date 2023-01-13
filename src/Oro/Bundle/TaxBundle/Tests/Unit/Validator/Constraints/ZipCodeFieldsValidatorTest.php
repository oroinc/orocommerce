<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\TaxBundle\Entity\ZipCode;
use Oro\Bundle\TaxBundle\Tests\Component\ZipCodeTestHelper;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFields;
use Oro\Bundle\TaxBundle\Validator\Constraints\ZipCodeFieldsValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ZipCodeFieldsValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var ZipCodeFieldsValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new ZipCodeFieldsValidator();
        $this->validator->initialize($this->context);
    }

    public function testGetTargets()
    {
        $constraint = new ZipCodeFields();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(ZipCode $zipCode, array $violationContext = [], array $violationCounts = [])
    {
        $constraint = new ZipCodeFields();
        $validator = $this->createMock(ValidatorInterface::class);

        $validator->expects($this->any())
            ->method('validate')
            ->willReturnCallback(function ($value) use ($violationCounts) {
                $list = $this->createMock(ConstraintViolationListInterface::class);

                if (!array_key_exists($value, $violationCounts)) {
                    $list->expects($this->once())
                        ->method('count')
                        ->willReturn(0);

                    return $list;
                }

                $list->expects($this->once())
                    ->method('count')
                    ->willReturn($violationCounts[$value]);

                return $list;
            });

        $this->context->expects($this->any())
            ->method('getValidator')
            ->willReturn($validator);

        if (0 === count($violationContext)) {
            $this->context->expects($this->never())
                ->method('buildViolation');
        } else {
            $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
            $builder->expects($this->once())
                ->method('atPath')
                ->with($violationContext[0])->willReturn($builder);
            $builder->expects($this->once())
                ->method('addViolation');
            $this->context->expects($this->once())
                ->method('buildViolation')
                ->with($constraint->{$violationContext[1]})
                ->willReturn($builder);
        }

        $this->validator->validate($zipCode, $constraint);
    }

    public function validateProvider(): array
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
        $constraint = new ZipCodeFields();

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('zipRangeStart')
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addViolation');
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->zipCodeCanNotBeEmpty)
            ->willReturn($builder);

        $this->validator->validate(new ZipCode(), $constraint);
    }

    public function testValidateWrongEntity()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\TaxBundle\Entity\ZipCode", "stdClass" given'
        );

        $constraint = new ZipCodeFields();
        $this->validator->validate(new \stdClass(), $constraint);
    }
}

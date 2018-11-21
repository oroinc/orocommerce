<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRate;
use Oro\Bundle\TaxBundle\Validator\Constraints\TaxRateValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class TaxRateValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TaxRateValidator */
    private $validator;

    /** @var TaxRate */
    private $constraint;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->validator = new TaxRateValidator();
        $this->constraint = new TaxRate();
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator->initialize($this->context);
    }

    /**
     * @param mixed $value
     * @param bool $expectedIsValid
     *
     * @dataProvider validateProvider
     */
    public function testValidate($value, $expectedIsValid)
    {
        if ($expectedIsValid) {
            $this->context->expects(self::never())
                ->method('addViolation');
        } else {
            $this->context->expects(self::once())
                ->method('addViolation')
                ->with($this->constraint->taxRateToManyDecimalPlaces);
        }

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            [
                'value' => 25,
                'expectedIsValid' => true,
            ],
            [
                'value' => 25.12,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.1,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.10,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.123456,
                'expectedIsValid' => true,
            ],
            [
                'value' => 0.1234567,
                'expectedIsValid' => false,
            ],
            [
                'value' => 0.0000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 0.00000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 11.0000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 11.00000001,
                'expectedIsValid' => false,
            ],
            [
                'value' => 'ab',
                'expectedIsValid' => true,
            ],
        ];
    }
}

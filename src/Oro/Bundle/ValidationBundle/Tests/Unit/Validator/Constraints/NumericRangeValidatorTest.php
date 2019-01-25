<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRange;
use Oro\Bundle\ValidationBundle\Validator\Constraints\NumericRangeValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class NumericRangeValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @param mixed $value
     * @param NumericRange $constraint
     * @param array $expectedViolation
     * @dataProvider validationDataProvider
     */
    public function testValidation($value, NumericRange $constraint, array $expectedViolation): void
    {
        $this->validator->validate($value, $constraint);

        if (!$expectedViolation) {
            $this->assertNoViolation();
        } else {
            $violation = $this->buildViolation($expectedViolation['message']);

            if (isset($expectedViolation['parameter'])) {
                $violation = $violation->setParameter('{{ limit }}', $expectedViolation['parameter']);
            }

            if (isset($expectedViolation['code'])) {
                $violation = $violation->setCode($expectedViolation['code']);
            }

            $violation->assertRaised();
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function validationDataProvider(): array
    {
        return [
            [
                'value' => null,
                'constraint' => new NumericRange(),
                'expectedViolation' => [],
            ],
            [
                'value' => 10,
                'constraint' => new NumericRange(),
                'expectedViolation' => [],
            ],
            [
                'value' => '10',
                'constraint' => new NumericRange(),
                'expectedViolation' => [],
            ],
            [
                'value' => 10.00,
                'constraint' => new NumericRange(),
                'expectedViolation' => [],
            ],
            [
                'value' => '999999999999999.9999',
                'constraint' => new NumericRange(),
                'expectedViolation' => [],
            ],
            [
                'value' => '1000000000000000',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '999999999999999.9999',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '999999999999999.9999000000000000001',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '999999999999999.9999',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '159132647246919822550452576.9150983494511948540991657',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '999999999999999.9999',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '-0.000000000000000000000000000000000000001',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or more.',
                    'parameter' => '0',
                    'code' => NumericRange::TOO_LOW_ERROR,
                ],
            ],
            [
                'value' => '-100.9150',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or more.',
                    'parameter' => '0',
                    'code' => NumericRange::TOO_LOW_ERROR,
                ],
            ],
            [
                'value' => 'ten',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be a valid number.',
                    'code' => NumericRange::INVALID_CHARACTERS_ERROR,
                ],
            ],
            [
                'value' => 'some string instead of number',
                'constraint' => new NumericRange(),
                'expectedViolation' => [
                    'message' => 'This value should be a valid number.',
                    'code' => NumericRange::INVALID_CHARACTERS_ERROR,
                ],
            ],
            [
                'value' => 100.001,
                'constraint' => new NumericRange(['max' => 100]),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '100',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => -100.001,
                'constraint' => new NumericRange(['min' => '-100']),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or more.',
                    'parameter' => '-100',
                    'code' => NumericRange::TOO_LOW_ERROR,
                ],
            ],
            [
                'value' => '-99.99999999999999999999999999999999999999999',
                'constraint' => new NumericRange(['min' => '-100']),
                'expectedViolation' => [],
            ],
            [
                'value' => 1000,
                'constraint' => new NumericRange(['precision' => 3, 'scale' => 0]),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '999',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '9.990001',
                'constraint' => new NumericRange(['precision' => 3, 'scale' => 2]),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '9.99',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => 9,
                'constraint' => new NumericRange(['precision' => 3, 'scale' => 2]),
                'expectedViolation' => [],
            ],
            [
                'value' => '99999999999999999.991',
                'constraint' => new NumericRange(['scale' => 2]),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '99999999999999999.99',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '9.99991',
                'constraint' => new NumericRange(['precision' => 5]),
                'expectedViolation' => [
                    'message' => 'This value should be {{ limit }} or less.',
                    'parameter' => '9.9999',
                    'code' => NumericRange::TOO_HIGH_ERROR,
                ],
            ],
            [
                'value' => '9.9998',
                'constraint' => new NumericRange(['precision' => 5]),
                'expectedViolation' => [],
            ]
        ];
    }

    /**
     * @return NumericRangeValidator
     */
    protected function createValidator()
    {
        return new NumericRangeValidator();
    }
}

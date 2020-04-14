<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Alphanumeric;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AlphanumericTest extends \PHPUnit\Framework\TestCase
{
    /** @var Alphanumeric */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var RegexValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new Alphanumeric();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new RegexValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Symfony\Component\Validator\Constraints\RegexValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('alphanumeric', $this->constraint->getAlias());
    }

    public function testGetDefaultOption()
    {
        $this->assertEquals(null, $this->constraint->getDefaultOption());
    }

    /**
     * @dataProvider validateDataProvider
     * @param mixed $data
     * @param boolean $correct
     */
    public function testValidate($data, $correct)
    {
        if ($correct) {
            $this->context->expects($this->never())
                ->method('buildViolation');
        } else {
            $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
            $this->context->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message)
                ->willReturn($builder);
            $builder->expects($this->once())
                ->method('setParameter')
                ->willReturnSelf();
            $builder->expects($this->once())
                ->method('setCode')
                ->willReturnSelf();
            $builder->expects($this->once())
                ->method('addViolation');
        }

        $this->validator->validate($data, $this->constraint);
    }

    public function validateDataProvider()
    {
        return [
            'alphanumeric' => [
                'data' => '10ten',
                'correct' => true
            ],
            'int number' => [
                'data' => 10,
                'correct' => true
            ],
            'alphabet' => [
                'data' => 'abcdefg',
                'correct' => true
            ],
            'decimal' => [
                'data' => 3.14,
                'correct' => false
            ],
            'symbols' => [
                'data' => '!@#test',
                'correct' => false
            ],
            'alphanumeric with space' => [
                'data' => '10 ten',
                'correct' => false
            ]
        ];
    }
}

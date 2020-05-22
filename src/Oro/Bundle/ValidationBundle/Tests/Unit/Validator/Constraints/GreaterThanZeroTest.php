<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThanValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class GreaterThanZeroTest extends \PHPUnit\Framework\TestCase
{
    /** @var GreaterThanZero */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var GreaterThanZero */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new GreaterThanZero();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new GreaterThanValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Symfony\Component\Validator\Constraints\GreaterThanValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('greater_than_zero', $this->constraint->getAlias());
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
        if (!$correct) {
            $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
            $this->context->expects($this->once())
                ->method('buildViolation')
                ->with($this->constraint->message)
                ->willReturn($builder);
            $builder->expects($this->exactly(3))
                ->method('setParameter')
                ->willReturnSelf();
            $builder->expects($this->once())
                ->method('setCode')
                ->willReturnSelf();
            $builder->expects($this->once())
                ->method('addViolation');
        } else {
            $this->context->expects($this->never())
                ->method('buildViolation');
        }

        $this->validator->validate($data, $this->constraint);
    }

    public function validateDataProvider()
    {
        return [
            'correct' => [
                'data' => 20,
                'correct' => true
            ],
            'zero' => [
                'data' => 0,
                'correct' => false
            ],
            'not correct' => [
                'data' => -20,
                'correct' => false
            ]
        ];
    }
}

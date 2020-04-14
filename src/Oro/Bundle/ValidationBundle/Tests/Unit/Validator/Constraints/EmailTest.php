<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    /** @var Email */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var EmailValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new Email();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new EmailValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Symfony\Component\Validator\Constraints\EmailValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('email', $this->constraint->getAlias());
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
            $builder->expects($this->once())
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
                'data' => 'test_123@test.com',
                'correct' => true
            ],
            'not correct' => [
                'data' => 'test.com',
                'correct' => false
            ]
        ];
    }
}

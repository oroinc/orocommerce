<?php

namespace OroB2B\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\EmailValidator;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Email;

class EmailTestTest extends \PHPUnit_Framework_TestCase
{
    /** @var Email */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var EmailValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new Email();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
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
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with($this->constraint->message);
        } else {
            $this->context->expects($this->never())
                ->method('addViolation');
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

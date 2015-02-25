<?php

namespace OroB2B\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThanValidator;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\GreaterThanZero;

class GreaterThanZeroTest extends \PHPUnit_Framework_TestCase
{
    /** @var GreaterThanZero */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var GreaterThanZero */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new GreaterThanZero();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
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

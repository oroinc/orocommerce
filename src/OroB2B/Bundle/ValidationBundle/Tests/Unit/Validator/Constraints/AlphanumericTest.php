<?php

namespace OroB2B\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\Alphanumeric;

class AlphanumericTest extends \PHPUnit_Framework_TestCase
{
    /** @var Alphanumeric */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var RegexValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new Alphanumeric();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
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
                ->method('addViolation');
        } else {
            $this->context->expects($this->once())
                ->method('addViolation')
                ->with($this->constraint->message);
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

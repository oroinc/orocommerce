<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Validator\Constraints;

use OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal;
use OroB2B\Bundle\AttributeBundle\Validator\Constraints\DecimalValidator;
use Symfony\Component\Validator\Constraint;

class DecimalTest extends \PHPUnit_Framework_TestCase
{
    /** @var \OroB2B\Bundle\AttributeBundle\Validator\Constraints\Decimal */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var DecimalValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new Decimal();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->validator = new DecimalValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'OroB2B\Bundle\AttributeBundle\Validator\Constraints\DecimalValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('decimal', $this->constraint->getAlias());
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
            'int' => [
                'data' => 10,
                'correct' => true
            ],
            'float' => [
                'data' => 10.45650,
                'correct' => true
            ],
            'string float' => [
                'data' => '10.4565',
                'correct' => true
            ],
            'null' => [
                'data' => null,
                'correct' => true
            ],
            'string with float' => [
                'data' => '10.45650 string',
                'correct' => false
            ],
        ];
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotScalar()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }
}

<?php

namespace OroB2B\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;

use OroB2B\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;

class UrlSafeTest extends \PHPUnit_Framework_TestCase
{
    /** @var UrlSafe */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Validator\ExecutionContextInterface */
    protected $context;

    /** @var RegexValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new UrlSafe();
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
        $this->assertEquals('url_safe', $this->constraint->getAlias());
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
            'Url safe' => [
                'data' => 'ABC-abs_123~45.test',
                'correct' => true
            ],
            'Url not safe' => [
                'data' => 'Abc/test',
                'correct' => false
            ],
        ];
    }

    public function testGetDefaultOption()
    {
        $this->assertEquals(null, $this->constraint->getDefaultOption());
    }
}

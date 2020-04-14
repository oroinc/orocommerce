<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Integer;
use Oro\Bundle\ValidationBundle\Validator\Constraints\IntegerValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class IntegerTest extends \PHPUnit\Framework\TestCase
{
    /** @var Integer */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var IntegerValidator */
    protected $validator;

    /** @var string */
    protected $locale;

    protected function setUp(): void
    {
        $this->constraint = new Integer();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new IntegerValidator();
        $this->validator->initialize($this->context);

        $this->locale = \Locale::getDefault();
        \Locale::setDefault('en');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);

        unset($this->constraint, $this->context, $this->validator, $this->locale);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Oro\Bundle\ValidationBundle\Validator\Constraints\IntegerValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('integer', $this->constraint->getAlias());
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
            'int number' => [
                'data' => 10,
                'correct' => true
            ],
            'string with int' => [
                'data' => '10',
                'correct' => true
            ],
            'int with separator' => [
                'data' => '10,000',
                'correct' => true
            ],
            'int with precision ' => [
                'data' => 10.00,
                'correct' => true
            ],
            'float number' => [
                'data' => 10.50,
                'correct' => false
            ],
            'string' => [
                'data' => 'ten',
                'correct' =>false
            ],
            'null' => [
                'data' => null,
                'correct' => true
            ],
        ];
    }

    public function testNotScalar()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), $this->constraint);
    }
}

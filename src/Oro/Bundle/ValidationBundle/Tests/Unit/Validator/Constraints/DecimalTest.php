<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Decimal;
use Oro\Bundle\ValidationBundle\Validator\Constraints\DecimalValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class DecimalTest extends \PHPUnit\Framework\TestCase
{
    /** @var Decimal */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var DecimalValidator */
    protected $validator;

    /** @var string */
    protected $locale;

    protected function setUp(): void
    {
        $this->constraint = new Decimal();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new DecimalValidator();
        $this->validator->initialize($this->context);

        $this->locale = \Locale::getDefault();
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);

        unset($this->constraint, $this->context, $this->validator, $this->locale);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            DecimalValidator::class,
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
     * @param string $locale
     */
    public function testValidate($data, $correct, $locale = 'en')
    {
        \Locale::setDefault($locale);
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
            'string float with trailing zeros fr' => [
                'data' => '10.4560000000000000000000',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float with trailing zeros without fraction part fr' => [
                'data' => '10.0000000000000000000',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float 100 fr' => [
                'data' => '100',
                'correct' => true,
                'locale' => 'fr'
            ],
            'string float fr' => [
                'data' => '10.456500000000000000000001',
                'correct' => false,
                'locale' => 'fr'
            ],
            'string float with grouping' => [
                'data' => '12,210.4565',
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

    public function testNotScalar()
    {
        $this->expectException(\Symfony\Component\Validator\Exception\UnexpectedTypeException::class);
        $this->validator->validate(new \stdClass(), $this->constraint);
    }
}

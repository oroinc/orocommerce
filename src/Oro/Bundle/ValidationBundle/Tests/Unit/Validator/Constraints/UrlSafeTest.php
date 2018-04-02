<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UrlSafeTest extends \PHPUnit_Framework_TestCase
{
    /** @var UrlSafe */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface */
    protected $context;

    /** @var RegexValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new UrlSafe();
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
                ->method('buildViolation');
        } else {
            $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

            $this->context->expects($this->once())->method('buildViolation')
                ->with($this->constraint->message)->willReturn($violationBuilder);
            $violationBuilder->expects($this->once())->method('setParameter')
                ->willReturnSelf();
            $violationBuilder->expects($this->once())->method('setCode')
                ->willReturnSelf();
            $violationBuilder->expects($this->once())
                ->method('addViolation');
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

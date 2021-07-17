<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\UrlSafe;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\RegexValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UrlSafeTest extends \PHPUnit\Framework\TestCase
{
    /** @var UrlSafe */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var RegexValidator */
    protected $validator;

    protected function setUp(): void
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
     *
     * @param bool $allowSlashes
     * @param mixed $data
     * @param boolean $correct
     */
    public function testValidate(bool $allowSlashes, $data, bool $correct): void
    {
        $constraint = new UrlSafe(['allowSlashes' => $allowSlashes]);

        if ($correct) {
            $this->context->expects($this->never())
                ->method('buildViolation');
        } else {
            $violationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);

            $this->context->expects($this->once())->method('buildViolation')
                ->with($constraint->message)->willReturn($violationBuilder);
            $violationBuilder->expects($this->once())->method('setParameter')
                ->willReturnSelf();
            $violationBuilder->expects($this->once())->method('setCode')
                ->willReturnSelf();
            $violationBuilder->expects($this->once())
                ->method('addViolation');
        }

        $this->validator->validate($data, $constraint);
    }

    public function validateDataProvider(): array
    {
        return [
            'Url safe' => [
                'allowSlashes' => false,
                'data' => 'ABC-abs_123~45.test',
                'correct' => true
            ],
            'Url not safe' => [
                'allowSlashes' => false,
                'data' => 'Abc/test',
                'correct' => false
            ],
            'Url safe with slash' => [
                'allowSlashes' => true,
                'data' => 'ABC-abs_123~45.test/ABC-abs_123~45.test',
                'correct' => true
            ],
            'Url not safe with slash on start' => [
                'allowSlashes' => true,
                'data' => '/Abc/test',
                'correct' => false
            ],
            'Url not safe with slash on end' => [
                'allowSlashes' => true,
                'data' => 'Abc/test/',
                'correct' => false
            ],
        ];
    }

    public function testGetDefaultOption()
    {
        $this->assertEquals(null, $this->constraint->getDefaultOption());
    }
}

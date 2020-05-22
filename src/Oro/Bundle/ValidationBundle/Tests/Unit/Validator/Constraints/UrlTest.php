<?php

namespace Oro\Bundle\ValidationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ValidationBundle\Validator\Constraints\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\UrlValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class UrlTest extends \PHPUnit\Framework\TestCase
{
    /** @var Url */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ExecutionContextInterface */
    protected $context;

    /** @var UrlValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->constraint = new Url();
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator = new UrlValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(
            'Symfony\Component\Validator\Constraints\UrlValidator',
            $this->constraint->validatedBy()
        );
        $this->assertEquals(Constraint::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    public function testGetAlias()
    {
        $this->assertEquals('url', $this->constraint->getAlias());
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
        }

        $this->validator->validate($data, $this->constraint);
    }

    public function validateDataProvider()
    {
        return [
            'Url safe' => [
                'data' => 'http://www.test.com/test',
                'correct' => true
            ],
            'Url not safe' => [
                'data' => '_Abc/test',
                'correct' => false
            ],
        ];
    }
}

<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ConsentBundle\Form\Type\ConsentSelectWithPriorityType;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfig;
use Oro\Bundle\ConsentBundle\SystemConfig\ConsentConfigConverter;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsent;
use Oro\Bundle\ConsentBundle\Validator\Constraints\UniqueConsentValidator;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilder;

class UniqueConsentValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var  UniqueConsent */
    protected $constraint;

    /** @var  UniqueConsentValidator */
    protected $validator;

    public function setUp()
    {
        parent::setUp();
        $this->constraint = new UniqueConsent();
        $this->validator = new UniqueConsentValidator();
    }

    public function testValidationOnValid()
    {
        $context = $this->getContextMock();

        $this->validator->initialize($context);

        $context->expects($this->never())
            ->method('buildViolation');

        $this->validator->validate($this->createConfigsData(2), $this->constraint);
    }

    public function testValidationOnValidConsentIdIsNull()
    {
        $context = $this->getContextMock();

        $this->validator->initialize($context);

        $context->expects($this->never())
            ->method('buildViolation');

        $configsData = [[ConsentConfigConverter::CONSENT_KEY => null]];

        $this->validator->validate($configsData, $this->constraint);
    }

    public function testValidationOnInvalid()
    {
        $builder = $this->getBuilderMock();

        $builder->expects($this->once())
            ->method('atPath')
            ->with('[2].consent')
            ->willReturn($builder);

        $context = $this->getContextMock();
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($this->equalTo($this->constraint->message), [])
            ->will($this->returnValue($builder));

        $this->validator->initialize($context);

        $value = array_merge($this->createConfigsData(2), $this->createConfigsData(1));
        $this->validator->validate($value, $this->constraint);
    }

    /**
     * @return ExecutionContext|\PHPUnit\Framework\MockObject\MockObject $context
     */
    protected function getContextMock()
    {
        return $this->createMock(ExecutionContext::class);
    }

    /**
     * @return ConstraintViolationBuilder|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getBuilderMock()
    {
        return $this->createMock(ConstraintViolationBuilder::class);
    }

    /**
     * @param int $count
     * @return ConsentConfig[]
     */
    public function createConfigsData($count)
    {
        $result = [];

        for ($i = 1; $i <= $count; $i++) {
            $result[] = [
                ConsentConfigConverter::CONSENT_KEY => $i,
                ConsentConfigConverter::SORT_ORDER_KEY => $i * 100,
            ];
        }

        return $result;
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Oro\Bundle\CustomerBundle\Entity\AccountAddress;
use Oro\Bundle\CustomerBundle\Entity\AccountUserAddress;
use Oro\Bundle\OrderBundle\Validator\Constraints\OrderAddressValidator;
use Oro\Bundle\OrderBundle\Validator\Constraints\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderAddress as OrderAddressEntity;

class OrderAddressTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LineItemProduct
     */
    protected $constraint;

    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    /**
     * @var OrderAddressValidator
     */
    protected $orderAddressValidator;

    /**
     * @var ExecutionContextInterface
     */
    protected $context;

    protected function setUp()
    {
        $this->constraint = new OrderAddress(['validationGroups' => ['Default', 'AbstractAddress', 'Frontend']]);
        $this->validator = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $this->context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->orderAddressValidator = new OrderAddressValidator($this->validator);
        $this->orderAddressValidator->initialize($this->context);
    }

    public function testGetTargets()
    {
        $this->assertEquals(OrderAddress::PROPERTY_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     * @expectedExceptionMessage
     * Expected argument of type "Oro\Bundle\OrderBundle\Validator\Constraints\ConstraintByValidationGroups"
     */
    public function testValidateException()
    {
        $this->orderAddressValidator->initialize($this->context);
        $this->orderAddressValidator->validate(null, $this->getMock('Symfony\Component\Validator\Constraint'));
    }

    public function testValidate()
    {
        $value = new OrderAddressEntity();
        $constraintViolation = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock();
        $constraintViolation->expects($this->once())->method('getParameters')->willReturn([]);
        $constraintViolation->expects($this->once())->method('getPropertyPath')->willReturn('street');
        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($value, null, ['Default', 'AbstractAddress', 'Frontend'])
            ->willReturn([$constraintViolation]);
        $violationBuilder = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $violationBuilder->expects($this->once())->method('atPath')->willReturnSelf();
        $this->context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($violationBuilder);

        $this->orderAddressValidator->validate($value, $this->constraint);
    }

    public function testValidateAccountAddress()
    {
        $value = new OrderAddressEntity();
        $value->setAccountAddress(new AccountAddress());
        $this->validator->expects($this->never())->method('validate');

        $this->orderAddressValidator->validate($value, $this->constraint);
        $value->setAccountAddress(null);
        $value->setAccountUserAddress(new AccountUserAddress());
        $this->orderAddressValidator->validate($value, $this->constraint);
    }
}

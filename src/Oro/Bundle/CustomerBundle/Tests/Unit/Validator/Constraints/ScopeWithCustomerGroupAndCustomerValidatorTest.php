<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Validator\Constraints\ScopeWithCustomerGroupAndCustomer;
use Oro\Bundle\CustomerBundle\Validator\Constraints\ScopeWithCustomerGroupAndCustomerValidator;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ScopeWithCustomerGroupAndCustomerValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    public function testValidateEmptyCollection()
    {
        $value = $this->createMock(Collection::class);
        $value->expects($this->once())
            ->method('isEmpty')
            ->willReturn(true);

        /** @var Constraint|\PHPUnit_Framework_MockObject_MockObject $constraint **/
        $constraint = $this->getMockBuilder(Constraint::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context->expects($this->never())
            ->method('addViolation');

        $validator = new ScopeWithCustomerGroupAndCustomerValidator();
        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }

    public function testValidateNotValidCollection()
    {
        $index = 1;
        $notValidScope = new StubScope([
            'customer' => $this->getEntity(Customer::class, ['id' => 123]),
            'customerGroup' => $this->getEntity(CustomerGroup::class, ['id' => 42]),
        ]);

        $value = $this->createMock(Collection::class);
        $value->expects($this->once())
            ->method('isEmpty')
            ->willReturn(false);

        $value->expects($this->once())
            ->method('getValues')
            ->willReturn([$index => $notValidScope]);

        $constraint = new ScopeWithCustomerGroupAndCustomer();

        $builder = $this->createMock('\Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
        $builder->expects($this->once())
            ->method('atPath')
            ->with("[$index]")
            ->willReturn($builder);
        $builder->expects($this->once())
            ->method('addViolation');

        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message)
            ->willReturn($builder);

        $validator = new ScopeWithCustomerGroupAndCustomerValidator();
        $validator->initialize($context);
        $validator->validate($value, $constraint);
    }
}

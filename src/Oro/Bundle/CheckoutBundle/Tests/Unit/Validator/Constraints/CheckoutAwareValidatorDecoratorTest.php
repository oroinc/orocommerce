<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Helper\CheckoutWorkflowHelper;
use Oro\Bundle\CheckoutBundle\Validator\Constraints\CheckoutAwareValidatorDecorator;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderAwareInterface;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CheckoutAwareValidatorDecoratorTest extends TestCase
{
    private ConstraintValidator|MockObject $innerValidator;

    private CheckoutWorkflowHelper|MockObject $checkoutWorkflowHelper;

    private CheckoutAwareValidatorDecorator $decorator;

    private ExecutionContextInterface|MockObject $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerValidator = $this->createMock(ConstraintValidator::class);
        $this->checkoutWorkflowHelper = $this->createMock(CheckoutWorkflowHelper::class);

        $this->decorator = new CheckoutAwareValidatorDecorator($this->innerValidator, $this->checkoutWorkflowHelper);

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->decorator->initialize($this->context);
    }

    public function testValidateWhenNull(): void
    {
        $this->innerValidator
            ->expects(self::never())
            ->method(self::anything());

        $this->checkoutWorkflowHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->decorator->validate(null, $this->createMock(Constraint::class));
    }

    public function testValidateWhenNoCheckoutStepsOption(): void
    {
        $value = new \stdClass();
        $constraint = $this->createMock(Constraint::class);

        $this->innerValidator
            ->expects(self::once())
            ->method('initialize')
            ->with($this->context);

        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with($value, $constraint);

        $this->checkoutWorkflowHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsNotCheckout(): void
    {
        $value = new \stdClass();
        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::once())
            ->method('initialize')
            ->with($this->context);

        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with($value, $constraint);

        $this->checkoutWorkflowHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsProductLineItemsHolderAwareButHolderIsNotCheckout(): void
    {
        $value = $this->createMock(ProductLineItemsHolderAwareInterface::class);
        $value
            ->expects(self::once())
            ->method('getLineItemsHolder')
            ->willReturn($this->createMock(ProductLineItemsHolderInterface::class));

        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::once())
            ->method('initialize')
            ->with($this->context);

        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with($value, $constraint);

        $this->checkoutWorkflowHelper
            ->expects(self::never())
            ->method(self::anything());

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsProductLineItemsHolderAwareAndStepIsNotAllowed(): void
    {
        $value = $this->createMock(ProductLineItemsHolderAwareInterface::class);
        $checkout = new Checkout();
        $value
            ->expects(self::once())
            ->method('getLineItemsHolder')
            ->willReturn($checkout);

        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::never())
            ->method(self::anything())
            ->with($this->context);

        $workflowItem = (new WorkflowItem())
            ->setCurrentStep((new WorkflowStep())->setName('sample_step_2'));
        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsProductLineItemsHolderAwareAndStepIsAllowed(): void
    {
        $value = $this->createMock(ProductLineItemsHolderAwareInterface::class);
        $checkout = new Checkout();
        $value
            ->expects(self::once())
            ->method('getLineItemsHolder')
            ->willReturn($checkout);

        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::once())
            ->method('initialize')
            ->with($this->context);

        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with($value, $constraint);

        $workflowItem = (new WorkflowItem())
            ->setCurrentStep((new WorkflowStep())->setName('sample_step_1'));
        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($checkout)
            ->willReturn($workflowItem);

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsCheckoutAndStepIsNotAllowed(): void
    {
        $value = new Checkout();

        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::never())
            ->method(self::anything())
            ->with($this->context);

        $workflowItem = (new WorkflowItem())
            ->setCurrentStep((new WorkflowStep())->setName('sample_step_2'));
        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($value)
            ->willReturn($workflowItem);

        $this->decorator->validate($value, $constraint);
    }

    public function testValidateWhenValueIsCheckoutAndStepIsAllowed(): void
    {
        $value = new Checkout();

        $constraint = new NotNull();
        $constraint->payload['checkoutSteps'] = ['sample_step_1'];

        $this->innerValidator
            ->expects(self::once())
            ->method('initialize')
            ->with($this->context);

        $this->innerValidator
            ->expects(self::once())
            ->method('validate')
            ->with($value, $constraint);

        $workflowItem = (new WorkflowItem())
            ->setCurrentStep((new WorkflowStep())->setName('sample_step_1'));
        $this->checkoutWorkflowHelper
            ->expects(self::once())
            ->method('getWorkflowItem')
            ->with($value)
            ->willReturn($workflowItem);

        $this->decorator->validate($value, $constraint);
    }
}

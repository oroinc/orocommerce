<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\WorkflowState\Processor\Transition\Template;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\SaleBundle\WorkflowState\Processor\Transition\Template\VerifyQuoteTemplateDataProcessor;
use Oro\Bundle\WorkflowBundle\Processor\Context\TemplateResultType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class VerifyQuoteTemplateDataProcessorTest extends TestCase
{
    private const VALIDATION_GROUPS = [['groupA', 'groupB'], 'groupC'];

    private ValidatorInterface|MockObject $validator;

    private VerifyQuoteTemplateDataProcessor $processor;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processor = new VerifyQuoteTemplateDataProcessor($this->validator, self::VALIDATION_GROUPS);
    }

    /**
     * @dataProvider getProcessSkipNonApplicableContextDataProvider
     */
    public function testProcessSkipNonApplicableContext(
        string $transitionName,
        string $formName,
        $formData
    ): void {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getName')
            ->willReturn($formName);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($formData);

        $context = new TransitionContext();
        $context->setResultType(new TemplateResultType());
        $context->setTransitionName($transitionName);
        $context->setForm($form);

        $this->validator->expects(self::never())
            ->method('validate')
            ->withAnyParameters();

        $this->processor->process($context);

        self::assertSame($form, $context->getForm());
    }

    public function getProcessSkipNonApplicableContextDataProvider(): array
    {
        return [
            'wrong transition name' => [
                'transitionName' => 'name',
                'formName' => QuoteType::NAME,
                'formData' => new Quote(),
            ],
            'wrong form name' => [
                'transitionName' => 'verify_transition',
                'formName' => 'another_form',
                'formData' => new Quote(),
            ],
            'wrong form data' => [
                'transitionName' => 'verify_transition',
                'formName' => QuoteType::NAME,
                'formData' => new \stdClass(),
            ],
        ];
    }

    public function testProcessNoErrors(): void
    {
        $formData = new Quote();

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getName')
            ->willReturn(QuoteType::NAME);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($formData);
        $form->expects(self::never())
            ->method('addError')
            ->withAnyParameters();

        $context = new TransitionContext();
        $context->setResultType(new TemplateResultType());
        $context->setTransitionName('verify_transition');
        $context->setForm($form);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($formData, null, ValidationGroupUtils::resolveValidationGroups(self::VALIDATION_GROUPS))
            ->willReturn(new ConstraintViolationList([]));

        $this->processor->process($context);

        self::assertSame($form, $context->getForm());
    }

    public function testProcessWithErrors(): void
    {
        $formData = new Quote();

        $message = 'msg';
        $fieldName = 'validUntil';

        $constraintViolation = new ConstraintViolation($message, null, [], null, 'data.' . $fieldName, $formData);

        $formConfig = $this->createMock(FormConfigInterface::class);
        $formConfig->expects(self::any())
            ->method('getOption')
            ->with('error_mapping')
            ->willReturn([]);

        $form = $this->createMock(FormInterface::class);
        $form->expects(self::any())
            ->method('getName')
            ->willReturn(QuoteType::NAME);
        $form->expects(self::any())
            ->method('getData')
            ->willReturn($formData);
        $form->expects(self::any())
            ->method('getConfig')
            ->willReturn($formConfig);
        $form->expects(self::any())
            ->method('isSynchronized')
            ->willReturn(true);
        $form->expects(self::once())
            ->method('addError')
            ->with(new FormError($message, null, [], null, $constraintViolation));

        $context = new TransitionContext();
        $context->setResultType(new TemplateResultType());
        $context->setTransitionName('verify_transition');
        $context->setForm($form);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($formData, null, ValidationGroupUtils::resolveValidationGroups(self::VALIDATION_GROUPS))
            ->willReturn(
                new ConstraintViolationList([
                    new ConstraintViolation($message, null, [], null, $fieldName, $formData),
                ])
            );

        $this->processor->process($context);

        self::assertSame($form, $context->getForm());
    }
}

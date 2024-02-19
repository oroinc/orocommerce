<?php

namespace Oro\Bundle\SaleBundle\WorkflowState\Processor\Transition\Template;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Form\Type\QuoteType;
use Oro\Bundle\WorkflowBundle\Processor\Context\TransitionContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Validates {@see QuoteType} form data against specified validation groups and maps errors to the transition form.
 */
class VerifyQuoteTemplateDataProcessor implements ProcessorInterface
{
    private array $validationGroups = [];

    private ValidatorInterface $validator;

    public function __construct(
        ValidatorInterface $validator,
        array $validationGroups
    ) {
        $this->validator = $validator;
        $this->validationGroups = $validationGroups;
    }

    /**
     * @param TransitionContext $context
     */
    public function process(ContextInterface $context)
    {
        if (!$this->isApplicable($context)) {
            return;
        }

        $transitionForm = $context->getForm();

        $constraintViolationList = $this->validator->validate(
            $transitionForm->getData(),
            null,
            ValidationGroupUtils::resolveValidationGroups($this->validationGroups)
        );

        $this->mapViolationListToForm($transitionForm, $constraintViolationList);

        $context->setForm($transitionForm);
    }

    protected function isApplicable(TransitionContext $context): bool
    {
        return $context->getTransitionName() === 'verify_transition'
            && $context->getForm()->getName() === QuoteType::NAME
            && $context->getForm()->getData() instanceof Quote;
    }

    public function mapViolationListToForm(FormInterface $form, ConstraintViolationList $constraintViolationList): void
    {
        if ($constraintViolationList->count()) {
            $violationMapper = new ViolationMapper();

            /** @var ConstraintViolation $constraintViolation */
            foreach ($constraintViolationList as $constraintViolation) {
                $violationMapper->mapViolation(
                    $this->createFormConstraintViolation($constraintViolation),
                    $form
                );
            }
        }
    }

    private function createFormConstraintViolation(ConstraintViolation $constraintViolation): ConstraintViolation
    {
        return new ConstraintViolation(
            $constraintViolation->getMessage(),
            $constraintViolation->getMessageTemplate(),
            $constraintViolation->getParameters(),
            $constraintViolation->getRoot(),
            $constraintViolation->getPropertyPath()
                ? 'data.' . $constraintViolation->getPropertyPath()
                : $constraintViolation->getPropertyPath(),
            $constraintViolation->getInvalidValue(),
            $constraintViolation->getPlural(),
            $constraintViolation->getCode(),
            $constraintViolation->getConstraint(),
            $constraintViolation->getCause()
        );
    }
}

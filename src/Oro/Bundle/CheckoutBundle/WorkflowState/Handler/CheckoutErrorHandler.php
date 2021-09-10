<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Handler;

use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Adds checkout errors as flash messages.
 * Filters out workflow state error.
 */
class CheckoutErrorHandler
{
    const WORKFLOW_STATE_MESSAGE = 'oro.checkout.workflow.condition.content_of_order_was_changed.message';

    /** @var FlashBagInterface */
    protected $flashBag;

    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /**
     * Return form errors without workflow state errors
     *
     * @param FormErrorIterator $errorIterator
     * @return FormErrorIterator
     */
    public function filterWorkflowStateError(FormErrorIterator $errorIterator)
    {
        $errors = [];

        foreach ($errorIterator as $error) {
            if ($error instanceof FormErrorIterator) {
                $errors[] = $this->filterWorkflowStateError($error);
                continue;
            }

            if ($error->getMessage() === self::WORKFLOW_STATE_MESSAGE) {
                continue;
            }

            $errors[] = $error;
        }

        return new FormErrorIterator($errorIterator->getForm(), $errors);
    }

    /**
     * Add flash warning based on passed errors
     */
    public function addFlashWorkflowStateWarning(FormErrorIterator $errorIterator)
    {
        foreach ($errorIterator as $error) {
            if ($error instanceof FormErrorIterator) {
                $this->addFlashWorkflowStateWarning($error);
                continue;
            }

            if ($error->getMessage() !== self::WORKFLOW_STATE_MESSAGE) {
                continue;
            }

            $this->addUniqueWarningMessage($error->getMessage());
        }
    }

    /**
     * Returns workflow-related errors from FromErrorIterator.
     */
    public function getWorkflowErrors(FormErrorIterator $errorIterator): array
    {
        $errors = [[]];
        foreach ($errorIterator as $error) {
            if ($error instanceof FormErrorIterator) {
                $errors[] = $this->getWorkflowErrors($error);
                continue;
            }

            if ($error->getCause() instanceof ConstraintViolation) {
                $constraint = $error->getCause()->getConstraint();
                if ($constraint instanceof TransitionIsAllowed) {
                    $errors[] = [$error->getMessage()];
                }
            }
        }

        return array_values(array_unique(array_merge(...$errors)));
    }

    /**
     * @param string $message
     */
    protected function addUniqueWarningMessage($message)
    {
        $messages = $this->flashBag->peek('warning');

        $filteredMessages = array_filter($messages, static fn ($value) => $value === $message);

        if (count($filteredMessages) === 0) {
            $this->flashBag->add('warning', $message);
        }
    }
}

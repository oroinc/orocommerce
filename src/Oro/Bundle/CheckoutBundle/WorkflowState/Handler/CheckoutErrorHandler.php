<?php

namespace Oro\Bundle\CheckoutBundle\WorkflowState\Handler;

use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;

class CheckoutErrorHandler
{
    const WORKFLOW_STATE_MESSAGE = 'oro.checkout.workflow.condition.content_of_order_was_changed.message';

    /** @var FlashBagInterface */
    protected $flashBag;

    /**
     * @param FlashBagInterface $flashBag
     */
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
     *
     * @param FormErrorIterator $errorIterator
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
     * @param FormErrorIterator $errorIterator
     * @return bool
     */
    public function isCheckoutRestartRequired(FormErrorIterator $errorIterator)
    {
        foreach ($errorIterator as $error) {
            if ($error instanceof FormErrorIterator) {
                return $this->isCheckoutRestartRequired($error);
            }

            if ($error->getMessage() == TransitionIsAllowed::$workflowCanceledByTransitionMessage) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $message
     */
    protected function addUniqueWarningMessage($message)
    {
        $messages = $this->flashBag->peek('warning');

        $filteredMessages = array_filter($messages, function ($value) use ($message) {
            return $value === $message;
        });

        if (count($filteredMessages) === 0) {
            $this->flashBag->add('warning', $message);
        }
    }
}

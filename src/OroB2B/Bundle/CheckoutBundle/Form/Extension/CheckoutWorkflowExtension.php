<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class CheckoutWorkflowExtension extends AbstractTypeExtension
{
    /** @var FlashBagInterface */
    protected $flashBag;

    /**
     * @param FlashBagInterface $flashBag
     */
    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormErrorIterator $errors */
        $errors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : new FormErrorIterator($form, []);

        $view->vars['errors'] = $this->handleErrors($errors);
    }

    /**
     * @param FormErrorIterator $errorsIterator
     * @return FormErrorIterator
     */
    protected function handleErrors(FormErrorIterator $errorsIterator)
    {
        $errors = [];

        foreach ($errorsIterator as $error) {
            if ($error instanceof FormErrorIterator) {
                $errors[] = $this->handleErrors($error);
                continue;
            }

            if ($error instanceof FormError &&
                $error->getMessage() === 'orob2b.checkout.workflow.condition.content_of_order_was_changed.message') {
                $this->addUniqueWarningMessage($error->getMessage());
                continue;
            }

            $errors[] = $error;
        }

        return new FormErrorIterator($errorsIterator->getForm(), $errors);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return WorkflowTransitionType::class;
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

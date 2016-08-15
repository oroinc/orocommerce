<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

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
     * {@inheritdoc}
     */
    public function __construct(FlashBagInterface $flashBag)
    {
        $this->flashBag = $flashBag;
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormErrorIterator $errors */
        $errors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : [];
        $filteredErrors = [];

        foreach ($errors as $error) {
            if ($error instanceof FormError
                && $error->getMessage() === 'orob2b.checkout.workflow.condition.content_of_order_was_changed.message'
            ) {
                $this->addUniqueErrorMessage($error->getMessage());

                continue;
            }

            $filteredErrors[] = $error;
        }

        $view->vars['errors'] = new FormErrorIterator($form, $filteredErrors);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return WorkflowTransitionType::class;
    }

    /**
     * @param string $message
     */
    protected function addUniqueErrorMessage($message)
    {
        $errorMessages = $this->flashBag->peek('error');

        $filteredMessages = array_filter($errorMessages, function ($value) use ($message) {
            return $value === $message;
        });

        if (count($filteredMessages) === 0) {
            $this->flashBag->add('error', $message);
        }
    }
}

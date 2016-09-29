<?php

namespace Oro\Bundle\CheckoutBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\CheckoutBundle\WorkflowState\Handler\CheckoutErrorHandler;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class CheckoutWorkflowStateExtension extends AbstractTypeExtension
{
    /** @var CheckoutErrorHandler */
    protected $checkoutErrorHandler;

    /**
     * @param CheckoutErrorHandler $checkoutErrorHandler
     */
    public function __construct(CheckoutErrorHandler $checkoutErrorHandler)
    {
        $this->checkoutErrorHandler = $checkoutErrorHandler;
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormErrorIterator $errors */
        $errors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : new FormErrorIterator($form, []);

        $view->vars['errors'] = $this->checkoutErrorHandler->filterWorkflowStateError($errors);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return WorkflowTransitionType::class;
    }
}

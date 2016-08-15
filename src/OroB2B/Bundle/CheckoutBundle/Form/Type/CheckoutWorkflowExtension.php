<?php

namespace OroB2B\Bundle\CheckoutBundle\Form\Type;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Session\Session;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class CheckoutWorkflowExtension extends AbstractTypeExtension
{
    /** @var Session */
    protected $session;

    /**
     * @inheritDoc
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /** {@inheritdoc} */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        /** @var FormErrorIterator $errors */
        $errors = array_key_exists('errors', $view->vars) ? $view->vars['errors'] : [];
        $newErrors = [];

        foreach ($errors as $error) {
            if ($error instanceof FormError
                && $error->getMessage() === 'orob2b.checkout.workflow.condition.content_of_order_was_changed.message') {
                $this->session->getFlashBag()->add('error', $error->getMessage());

                continue;
            }

            $newErrors[] = $error;
        }

        $view->vars['errors'] = new FormErrorIterator($form, $newErrors);
    }

    /** {@inheritdoc} */
    public function getExtendedType()
    {
        return WorkflowTransitionType::class;
    }
}

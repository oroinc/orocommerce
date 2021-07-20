<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Environment;

/**
 * This is class that can be used as base for implementation of adding to the event, block with some view HTML,
 * generated based on the form field that contains submission.
 */
abstract class AbstractFormEventListener
{
    /** @var Environment */
    protected $twig;

    /** @var FormFactoryInterface */
    protected $formFactory;

    public function __construct(Environment $twig, FormFactoryInterface $formFactory)
    {
        $this->twig = $twig;
        $this->formFactory = $formFactory;
    }

    abstract public function onOrderEvent(OrderEvent $event);

    /**
     * @param FormInterface $orderForm
     * @param string $fieldName
     * @param array|null $submission
     *
     * @return FormInterface
     */
    protected function createFieldWithSubmission(FormInterface $orderForm, string $fieldName, $submission)
    {
        $orderFormName = $orderForm->getName();
        $field = $orderForm->get($fieldName);

        $form = $this->formFactory
            ->createNamedBuilder($orderFormName)
            ->add(
                $fieldName,
                get_class($field->getConfig()->getType()->getInnerType()),
                $field->getConfig()->getOptions()
            )
            ->getForm();

        $form->submit($submission);

        return $form;
    }

    /**
     * @param FormView $formView
     * @param string $template
     *
     * @return string
     */
    protected function renderForm(FormView $formView, string $template)
    {
        return $this->twig->render($template, ['form' => $formView]);
    }
}

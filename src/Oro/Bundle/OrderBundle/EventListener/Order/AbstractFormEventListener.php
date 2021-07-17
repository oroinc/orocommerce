<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Templating\EngineInterface;

/**
 * This is class that can be used as base for implementation of adding to the event, block with some view HTML,
 * generated based on the form field that contains submission.
 */
abstract class AbstractFormEventListener
{
    /** @var EngineInterface */
    protected $engine;

    /** @var FormFactoryInterface */
    protected $formFactory;

    public function __construct(EngineInterface $engine, FormFactoryInterface $formFactory)
    {
        $this->engine = $engine;
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
        return $this->engine->render($template, ['form' => $formView]);
    }
}

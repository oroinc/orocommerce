<?php

namespace OroB2B\Bundle\PaymentBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;

use OroB2B\Bundle\PaymentBundle\Form\Type\PaymentTermMethodType;

class PaymentTermFormProvider implements DataProviderInterface
{
    const NAME = 'orob2b_payment_payment_term_form_provider';
    /**
     * @var FormAccessor
     */
    protected $data;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if (!$this->data) {
            $this->data = new FormAccessor(
                $this->getForm()
            );
        }
        return $this->data;
    }

    /**
     * @param array $data
     * @param array $options
     * @return FormInterface
     */
    public function getForm($data = [], array $options = [])
    {
        if (!$this->form) {
            $this->form = $this->formFactory->create(PaymentTermMethodType::NAME, $data, $options);
        }
        return $this->form;
    }
}

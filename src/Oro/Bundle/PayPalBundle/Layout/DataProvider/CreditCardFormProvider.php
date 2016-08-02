<?php

namespace Oro\Bundle\PayPalBundle\Layout\DataProvider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Component\Layout\DataProviderInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

class CreditCardFormProvider implements DataProviderInterface
{
    const NAME = 'oro_paypal_credit_card_form_provider';

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
            $this->form = $this->formFactory->create(CreditCardType::NAME, $data, $options);
        }
        return $this->form;
    }
}

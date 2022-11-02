<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Factory;

use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView;
use Symfony\Component\Form\FormFactoryInterface;

class BasicPayPalCreditCardPaymentMethodViewFactory implements PayPalCreditCardPaymentMethodViewFactoryInterface
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var PaymentTransactionProvider
     */
    private $transactionProvider;

    public function __construct(FormFactoryInterface $formFactory, PaymentTransactionProvider $transactionProvider)
    {
        $this->formFactory = $formFactory;
        $this->transactionProvider = $transactionProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethodView($this->formFactory, $config, $this->transactionProvider);
    }
}

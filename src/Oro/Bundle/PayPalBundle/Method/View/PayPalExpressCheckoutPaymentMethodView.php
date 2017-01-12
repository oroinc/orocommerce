<?php

namespace Oro\Bundle\PayPalBundle\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayflowExpressCheckout;
use Symfony\Component\Form\FormFactoryInterface;

class PayPalExpressCheckoutPaymentMethodView implements PaymentMethodViewInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var PayPalExpressCheckoutConfigInterface */
    protected $config;

    /** @param PayPalExpressCheckoutConfigInterface $config */
    public function __construct(PayPalExpressCheckoutConfigInterface $config)
    {
        $this->config = $config;
    }

    /** {@inheritdoc} */
    public function getOptions(PaymentContextInterface $context)
    {
        return [];
    }

    /** {@inheritdoc} */
    public function getBlock()
    {
        return '_payment_methods_payflow_express_checkout_widget';
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}

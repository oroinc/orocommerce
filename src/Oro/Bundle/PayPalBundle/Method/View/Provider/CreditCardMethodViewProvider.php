<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\Provider\PayPalCreditCardConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView;
use Symfony\Component\Form\FormFactoryInterface;

class CreditCardMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var PaymentTransactionProvider */
    protected $transactionProvider;

    /** @var PayPalCreditCardConfigProviderInterface */
    protected $configProvider;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PaymentTransactionProvider $transactionProvider
     * @param PayPalCreditCardConfigProviderInterface $configProvider
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PaymentTransactionProvider $transactionProvider,
        PayPalCreditCardConfigProviderInterface $configProvider
    ) {
        $this->formFactory = $formFactory;
        $this->transactionProvider = $transactionProvider;
        $this->configProvider = $configProvider;

        parent::__construct();
    }

    protected function buildViews()
    {
        $configs = $this->configProvider->getPaymentConfigs();
        foreach ($configs as $config) {
            $this->addCreditCardView($config);
        }
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     */
    protected function addCreditCardView(PayPalCreditCardConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->buildView($config)
        );
    }

    /**
     * @param PayPalCreditCardConfigInterface $config
     *
     * @return PayPalCreditCardPaymentMethodView
     */
    protected function buildView(PayPalCreditCardConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethodView(
            $this->formFactory,
            $config,
            $this->transactionProvider
        );
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\Method\View\Provider;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\AbstractPaymentMethodViewProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView;
use Symfony\Component\Form\FormFactoryInterface;

class CreditCardMethodViewProvider extends AbstractPaymentMethodViewProvider
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var PaymentTransactionProvider */
    protected $transactionProvider;

    /** @var PaymentConfigProviderInterface */
    protected $configProvider;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PaymentTransactionProvider $transactionProvider
     * @param PaymentConfigProviderInterface $configProvider
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PaymentTransactionProvider $transactionProvider,
        PaymentConfigProviderInterface $configProvider
    ) {
        $this->formFactory = $formFactory;
        $this->configProvider = $configProvider;
        $this->transactionProvider = $transactionProvider;

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
     * @param PaymentConfigInterface $config
     */
    protected function addCreditCardView(PaymentConfigInterface $config)
    {
        $this->addView(
            $config->getPaymentMethodIdentifier(),
            $this->buildView($config)
        );
    }

    /**
     * @param PaymentConfigInterface $config
     *
     * @return PayPalCreditCardPaymentMethodView
     */
    protected function buildView(PaymentConfigInterface $config)
    {
        return new PayPalCreditCardPaymentMethodView(
            $this->formFactory,
            $config,
            $this->transactionProvider
        );
    }
}

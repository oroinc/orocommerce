<?php

namespace Oro\Bundle\PayPalBundle\Method\View;

use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowExpressCheckoutConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Symfony\Component\Form\FormFactoryInterface;

class PayPalMethodViewProvider implements PaymentMethodViewProviderInterface
{
    /** @var  PaymentMethodViewInterface[] */
    protected $methodViews;

    /** @var string */
    protected $providerType;
    
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var PaymentConfigInterface */
    protected $config;

    /**
     * @param string $providerType
     * @param PayflowGatewayConfigInterface|PayflowExpressCheckoutConfigInterface $config
     * @param FormFactoryInterface|null $formFactory
     * @param PaymentTransactionProvider|null $paymentTransactionProvider
     */
    public function __construct(
        $providerType,
        $config,
        FormFactoryInterface $formFactory = null,
        PaymentTransactionProvider $paymentTransactionProvider = null
    ) {
        $this->providerType = $providerType;
        $this->formFactory = $formFactory;
        $this->config = $config;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * @param array $paymentMethods
     * @return PaymentMethodViewInterface[]
     */
    public function getPaymentMethodViews(array $paymentMethods)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        $matchedViews = [];
        foreach ($paymentMethods as $paymentMethod) {
            $matchedViews[$paymentMethod] = $this->getPaymentMethodView($paymentMethod);
        }
        return $matchedViews;
    }

    /**
     * @param string $identifier
     * @return PaymentMethodViewInterface
     */
    public function getPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return $this->methodViews[$identifier];
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasPaymentMethodView($identifier)
    {
        if ($this->methodViews === null) {
            $this->collectPaymentMethodViews();
        }
        return array_key_exists($identifier, $this->methodViews);
    }

    protected function collectPaymentMethodViews()
    {
        switch ($this->providerType) {
            case 'payflow_gateway':
                $paymentMethodView = new PayflowGatewayView(
                    $this->formFactory,
                    $this->config,
                    $this->paymentTransactionProvider
                );
                break;
            case 'paypal_payments_pro':
                $paymentMethodView = new PayPalPaymentsProView(
                    $this->formFactory,
                    $this->config,
                    $this->paymentTransactionProvider
                );
                break;
            case 'payflow_express_checkout':
                $paymentMethodView = new PayflowExpressCheckoutView($this->config);
                break;
            case 'paypal_payments_pro_express_checkout':
                $paymentMethodView = new PayPalPaymentsProExpressCheckoutView($this->config);
                break;
        }

        $this->methodViews = [$this->providerType => $paymentMethodView];
    }
}

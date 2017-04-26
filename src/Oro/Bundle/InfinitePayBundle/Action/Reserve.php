<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProviderInterface;
use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;

class Reserve extends ActionAbstract
{
    /**
     * @var AutomationProviderInterface
     */
    protected $automationProvider;

    public function setAutomationProvider(AutomationProviderInterface $automationProvider)
    {
        $this->automationProvider = $automationProvider;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Order              $order
     *
     * @return array
     */
    public function execute(PaymentTransaction $paymentTransaction, Order $order)
    {
        $additionalOptions = $this->getAdditionalOptionsFromPaymentTransaction($paymentTransaction);

        $paymentMethodConfig = $this->getPaymentMethodConfig($paymentTransaction->getPaymentMethod());

        $reserveOrder = $this->requestMapper->createRequestFromOrder($order, $paymentMethodConfig, $additionalOptions);
        $reserveOrder = $this->automationProvider->setAutomation(
            $reserveOrder,
            $order,
            $paymentMethodConfig
        );
        $paymentResponse = $this->gateway->reserve(
            $reserveOrder,
            $paymentMethodConfig
        );

        $paymentTransaction = $this->responseMapper->mapResponseToPaymentTransaction(
            $paymentTransaction,
            $paymentResponse
        );
        $paymentTransaction->setSuccessful(
            $this->isSuccessfulAutoActivation($paymentTransaction, $paymentMethodConfig)
        );

        return $this->createResponseFromPaymentTransaction($paymentTransaction);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     */
    private function createResponseFromPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $response = ['success' => $paymentTransaction->isActive()];
        if (!$paymentTransaction->isActive()) {
            $response['successUrl'] = null;
        }

        return $response;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     *
     * @return array
     */
    private function getAdditionalOptionsFromPaymentTransaction(PaymentTransaction $paymentTransaction)
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();

        return $transactionOptions['additionalOptions'];
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param InfinitePayConfigInterface $config
     * @return bool
     */
    private function isSuccessfulAutoActivation(
        PaymentTransaction $paymentTransaction,
        InfinitePayConfigInterface $config
    ) {
        return $config !== null && $config->isAutoActivateEnabled() && $paymentTransaction->isActive();
    }
}

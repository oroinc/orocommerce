<?php

namespace Oro\Bundle\InfinitePayBundle\Action;

use Oro\Bundle\InfinitePayBundle\Action\Provider\AutomationProviderInterface;
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

        $reserveOrder = $this->requestMapper->createRequestFromOrder($order, $additionalOptions);
        $reserveOrder = $this->automationProvider->setAutomation($reserveOrder, $order);
        $paymentResponse = $this->gateway->reserve($reserveOrder);

        $paymentTransaction = $this->responseMapper->mapResponseToPaymentTransaction(
            $paymentTransaction,
            $paymentResponse
        );
        $paymentTransaction->setSuccessful($this->isSuccessfullAutoActivation($paymentTransaction));

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

            return $response;
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
     *
     * @return bool
     */
    private function isSuccessfullAutoActivation(PaymentTransaction $paymentTransaction)
    {
        return $this->config->isAutoActivationActive() && $paymentTransaction->isActive();
    }
}

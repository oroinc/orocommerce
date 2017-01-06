<?php

namespace Oro\Bundle\PaymentBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProvider;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

class PaymentMethodViewsProvider
{
    /** @var PaymentMethodViewProvidersRegistry */
    protected $paymentMethodViewRegistry;

    /** @var PaymentMethodProvider */
    protected $paymentMethodProvider;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry
     * @param PaymentMethodProvider $paymentMethodProvider
     * @param PaymentTransactionProvider $transactionProvider
     */
    public function __construct(
        PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry,
        PaymentMethodProvider $paymentMethodProvider,
        PaymentTransactionProvider $transactionProvider
    ) {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentTransactionProvider = $transactionProvider;
    }

    /**
     * @param PaymentContextInterface $context
     * @return array[]
     */
    public function getViews(PaymentContextInterface $context)
    {
        $methods = $this->paymentMethodProvider->getApplicablePaymentMethods($context);

        if (count($methods) === 0) {
            return [];
        }

        $methodIdentifiers = array_map(function (PaymentMethodInterface $method) {
            return $method->getIdentifier();
        }, $methods);

        $paymentMethodViews = [];
        $views = $this->paymentMethodViewRegistry->getPaymentMethodViews($methodIdentifiers);
        foreach ($views as $view) {
            $paymentMethodViews[$view->getPaymentMethodIdentifier()] = [
                'label' => $view->getLabel(),
                'block' => $view->getBlock(),
                'options' => $view->getOptions($context),
            ];
        }

        return $paymentMethodViews;
    }

    /**
     * @param object $entity
     * @return array
     */
    public function getPaymentMethods($entity)
    {
        return $this->paymentTransactionProvider->getPaymentMethods($entity);
    }
}

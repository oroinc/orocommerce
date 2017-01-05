<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistry;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;

class PaymentTermViewProvider
{
    /**
     * @var PaymentMethodViewProvidersRegistry
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodProvidersRegistry
     */
    private $paymentMethodRegistry;

    /**
     * @param PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry
     * @param PaymentMethodProvidersRegistry $paymentMethodRegistry
     */
    public function __construct(
        PaymentMethodViewProvidersRegistry $paymentMethodViewRegistry,
        PaymentMethodProvidersRegistry $paymentMethodRegistry
    ) {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * @param PaymentContextInterface $context
     * @return array|null
     */
    public function getView(PaymentContextInterface $context)
    {
        try {
            $paymentMethod = $this->paymentMethodRegistry->getPaymentMethod(PaymentTerm::TYPE);
            if (!$paymentMethod->isApplicable($context)) {
                return null;
            }

            $view = $this->paymentMethodViewRegistry->getPaymentMethodView(PaymentTerm::TYPE);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        return [
            'label' => $view->getLabel(),
            'block' => $view->getBlock(),
            'options' => $view->getOptions($context),
        ];
    }
}

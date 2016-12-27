<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use Oro\Bundle\PaymentTermBundle\Method\PaymentTerm;

class PaymentTermViewProvider
{
    /**
     * @var PaymentMethodViewRegistry
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodRegistry
     */
    private $paymentMethodRegistry;

    /**
     * @param PaymentMethodViewRegistry $paymentMethodViewRegistry
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(
        PaymentMethodViewRegistry $paymentMethodViewRegistry,
        PaymentMethodRegistry $paymentMethodRegistry
    ) {
        $this->paymentMethodViewRegistry = $paymentMethodViewRegistry;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * @param PaymentContextInterface $context
     * @return \array|null
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

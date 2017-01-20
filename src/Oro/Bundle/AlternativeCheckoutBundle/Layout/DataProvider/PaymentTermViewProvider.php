<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
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
            $paymentMethodProvider = $this->getPaymentTermProvider();
            if (!$paymentMethodProvider) {
                return null;
            }
            $paymentMethods = [];
            foreach ($paymentMethodProvider->getPaymentMethods() as $paymentMethod) {
                if ($paymentMethod->isApplicable($context)) {
                    $paymentMethods[] = $paymentMethod->getIdentifier();
                }
            }
            if (count($paymentMethods) === 0) {
                return null;
            }

            $views = $this->paymentMethodViewRegistry->getPaymentMethodViews($paymentMethods);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        if (0 === count($views)) {
            return null;
        }

        return $this->formatPaymentViews($views, $context);
    }

    /**
     * @return null|PaymentMethodProviderInterface
     */
    private function getPaymentTermProvider()
    {
        $providers = $this->paymentMethodRegistry->getPaymentMethodProviders();
        foreach ($providers as $provider) {
            if ($provider->hasPaymentMethod(PaymentTerm::TYPE)) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * @param PaymentMethodViewInterface[] $views
     * @param PaymentContextInterface $context
     * @return array
     */
    protected function formatPaymentViews($views, PaymentContextInterface $context)
    {

        $paymentMethodViews = [];
        foreach ($views as $view) {
            $paymentMethodViews[$view->getPaymentMethodIdentifier()] = [
                'label' => $view->getLabel(),
                'block' => $view->getBlock(),
                'options' => $view->getOptions($context),
            ];
        }

        return $paymentMethodViews;
    }
}

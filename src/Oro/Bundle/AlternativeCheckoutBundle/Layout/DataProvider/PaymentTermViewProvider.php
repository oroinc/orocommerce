<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Layout\DataProvider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewProvidersRegistryInterface;

class PaymentTermViewProvider
{
    /**
     * @var PaymentMethodViewProvidersRegistryInterface
     */
    protected $paymentMethodViewProvidersRegistry;

    /**
     * @var PaymentMethodProviderInterface
     */
    private $paymentMethodProvider;

    /**
     * @param PaymentMethodViewProvidersRegistryInterface $paymentMethodViewProvidersRegistry
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     */
    public function __construct(
        PaymentMethodViewProvidersRegistryInterface $paymentMethodViewProvidersRegistry,
        PaymentMethodProviderInterface $paymentMethodProvider
    ) {
        $this->paymentMethodViewProvidersRegistry = $paymentMethodViewProvidersRegistry;
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * @param PaymentContextInterface $context
     * @return array|null
     */
    public function getView(PaymentContextInterface $context)
    {
        try {
            $paymentMethods = [];
            foreach ($this->paymentMethodProvider->getPaymentMethods() as $paymentMethod) {
                if ($paymentMethod->isApplicable($context)) {
                    $paymentMethods[] = $paymentMethod->getIdentifier();
                }
            }
            if (count($paymentMethods) === 0) {
                return null;
            }

            $views = $this->paymentMethodViewProvidersRegistry->getPaymentMethodViews($paymentMethods);
        } catch (\InvalidArgumentException $e) {
            return null;
        }

        if (0 === count($views)) {
            return null;
        }

        return $this->formatPaymentViews($views, $context);
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

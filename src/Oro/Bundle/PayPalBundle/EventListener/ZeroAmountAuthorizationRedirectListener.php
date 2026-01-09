<?php

namespace Oro\Bundle\PayPalBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;

/**
 * Handles payment redirect requirements for zero-amount authorization transactions.
 *
 * Determines whether a payment redirect is required based on the PayPal credit card
 * configuration's zero-amount authorization settings.
 */
class ZeroAmountAuthorizationRedirectListener
{
    /**
     * @var PayPalCreditCardConfigProviderInterface
     */
    private $configProvider;

    public function __construct(PayPalCreditCardConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function onRequirePaymentRedirect(RequirePaymentRedirectEvent $event)
    {
        $paymentMethodIdentifier = $event->getPaymentMethod()->getIdentifier();
        if ($this->configProvider->hasPaymentConfig($paymentMethodIdentifier)) {
            $config = $this->configProvider->getPaymentConfig($paymentMethodIdentifier);
            $event->setRedirectRequired(!$config->isZeroAmountAuthorizationEnabled());
        }
    }
}

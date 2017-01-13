<?php

namespace Oro\Bundle\PayPalBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;

class ZeroAmountAuthorizationRedirectListener
{
    /**
     * @var PayPalCreditCardConfigProviderInterface
     */
    private $config;

    /**
     * @param PayPalCreditCardConfigProviderInterface $config
     */
    public function __construct(PayPalCreditCardConfigProviderInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param RequirePaymentRedirectEvent $event
     */
    public function onRequirePaymentRedirect(RequirePaymentRedirectEvent $event)
    {
        $paymentConfig = $this->config->getPaymentConfig($event->getPaymentMethod()->getIdentifier());
        $event->setRedirectRequired(!$paymentConfig->isZeroAmountAuthorizationEnabled());
    }
}

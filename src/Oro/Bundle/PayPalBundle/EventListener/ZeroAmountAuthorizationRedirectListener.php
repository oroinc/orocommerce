<?php

namespace Oro\Bundle\PayPalBundle\EventListener;

use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;
use Oro\Bundle\PayPalBundle\Method\Config\Provider\PayPalCreditCardConfigProviderInterface;

class ZeroAmountAuthorizationRedirectListener
{
    /**
     * @var PayPalCreditCardConfigProviderInterface
     */
    private $configProvider;

    /**
     * @param PayPalCreditCardConfigProviderInterface $configProvider
     */
    public function __construct(PayPalCreditCardConfigProviderInterface $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param RequirePaymentRedirectEvent $event
     */
    public function onRequirePaymentRedirect(RequirePaymentRedirectEvent $event)
    {
        $config = $this->configProvider->getPaymentConfig($event->getPaymentMethod()->getIdentifier());
        $event->setRedirectRequired(!$config->isZeroAmountAuthorizationEnabled());
    }
}

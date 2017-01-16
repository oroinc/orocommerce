<?php

namespace Oro\Bundle\PayPalBundle\EventListener;

use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PaymentBundle\Event\RequirePaymentRedirectEvent;

class ZeroAmountAuthorizationRedirectListener
{
    /**
     * @var PayPalCreditCardConfigInterface
     */
    private $config;

    /**
     * @param PayPalCreditCardConfigInterface $config
     */
    public function __construct(PayPalCreditCardConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param RequirePaymentRedirectEvent $event
     */
    public function onRequirePaymentRedirect(RequirePaymentRedirectEvent $event)
    {
        $event->setRedirectRequired(!$this->config->isZeroAmountAuthorizationEnabled());
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;

class PayflowIPCheckListener
{
    /**
     * @var string[]
     */
    private $allowedIPs = [
        // Payflow Silent Post
        '173.0.81.1',
        '173.0.81.33',
        '173.0.81.0/24',

        // Payflow Silent Post Backup
        '66.211.170.66',
    ];

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    /**
     * @param RequestStack $requestStack
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     */
    public function __construct(RequestStack $requestStack, PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->requestStack = $requestStack;
        $this->paymentMethodProvider = $paymentMethodProvider;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        $masterRequest = $this->requestStack->getMasterRequest();
        if (null === $masterRequest) {
            $event->markFailed();

            return;
        }

        $requestIp = $masterRequest->getClientIp();

        if (!IpUtils::checkIp($requestIp, $this->allowedIPs)) {
            $event->markFailed();
        }
    }
}

<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Oro\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Security level that checks whether it is possible to use PayFlow from allowed ip addresses.
 */
class PayflowIPCheckListener
{
    /**
     * @var string[]
     */
    protected $allowedIPs = [
        '64.4.240.0/21',
        '64.4.248.0/22',
        '66.211.168.0/22',
        '91.243.72.0/23',
        '159.242.240.0/21',
        '173.0.80.0/20',
        '176.120.16.0/21',
        '184.105.254.0/24',
        '185.177.52.0/22',
        '198.54.216.0/23',
        '198.199.247.0/24',
        '204.109.13.0/24',
        '205.189.102.0/24',
        '205.189.103.0/24',
        '208.76.140.0/22'
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
     * @param string[] $allowedIPs
     */
    public function __construct(
        RequestStack $requestStack,
        PaymentMethodProviderInterface $paymentMethodProvider,
        array $allowedIPs
    ) {
        $this->requestStack = $requestStack;
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->allowedIPs = $allowedIPs ?: $this->allowedIPs;
    }

    public function onNotify(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction) {
            return;
        }

        if (false === $this->paymentMethodProvider->hasPaymentMethod($paymentTransaction->getPaymentMethod())) {
            return;
        }

        $masterRequest = $this->requestStack->getMainRequest();
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

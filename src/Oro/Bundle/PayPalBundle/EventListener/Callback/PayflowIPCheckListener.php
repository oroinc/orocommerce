<?php

namespace Oro\Bundle\PayPalBundle\EventListener\Callback;

use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\RequestStack;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;

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
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
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

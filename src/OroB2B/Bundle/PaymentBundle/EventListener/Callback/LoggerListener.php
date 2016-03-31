<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;
use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;

class LoggerListener
{
    use LoggerAwareTrait;

    /** @var Mcrypt */
    protected $mcrypt;

    /**
     * @param LoggerInterface $logger
     * @param Mcrypt $mcrypt
     */
    public function __construct(LoggerInterface $logger, Mcrypt $mcrypt)
    {
        $this->logger = $logger;
        $this->mcrypt = $mcrypt;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onCallback(AbstractCallbackEvent $event)
    {
        $this->logger->info(
            sprintf(
                'Payment transaction callback. Type: %s Data: %s',
                $event->getEventName(),
                $this->mcrypt->encryptData($event->getQueryString())
            )
        );
    }
}

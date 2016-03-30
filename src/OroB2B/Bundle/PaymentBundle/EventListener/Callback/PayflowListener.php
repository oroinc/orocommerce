<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Psr\Log\LoggerAwareTrait;

use Oro\Bundle\SecurityBundle\Encoder\Mcrypt;

use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;

class PayflowListener
{
    use LoggerAwareTrait;

    /**  @var EncoderInterface */
    protected $encoder;

    /** @var Mcrypt */
    protected $crypt;

    /**
     * @param EncoderInterface $encoder
     * @param Mcrypt $crypt
     */
    public function __construct(EncoderInterface $encoder, Mcrypt $crypt)
    {
        $this->encoder = $encoder;
        $this->crypt = $crypt;
    }

    /**
     * @param CallbackReturnEvent $event
     */
    public function onReturn(CallbackReturnEvent $event)
    {
        $data = $this->encoder->decode($event->getQueryString());

        if (!empty($data[Option\SecureToken::SECURETOKEN])) {
            return;
        }

        if (!empty($data[Option\SecureTokenIdentifier::SECURETOKENID])) {
            return;
        }

        $secureToken = $data[Option\SecureToken::SECURETOKEN];
        $secureTokenIdentifier = $data[Option\SecureTokenIdentifier::SECURETOKENID];

        $paymentTransaction = $event->getPaymentTransaction();

        if ($secureToken !== $paymentTransaction->getToken()) {
            return;
        }

        if ($secureTokenIdentifier !== $paymentTransaction->getIdentifier()) {
            return;
        }

        $paymentTransaction->setData($this->crypt->encryptData($event->getQueryString()));
    }

    /**
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
    }
}

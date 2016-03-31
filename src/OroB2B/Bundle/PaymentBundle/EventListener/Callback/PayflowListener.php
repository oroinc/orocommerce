<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\NVP\EncoderInterface;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;

class PayflowListener
{
    /**  @var EncoderInterface */
    protected $encoder;

    /**
     * @param EncoderInterface $encoder
     */
    public function __construct(EncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    /**
     * @param CallbackReturnEvent $event
     */
    public function onReturn(CallbackReturnEvent $event)
    {
        $this->handleEvent($event);
    }

    /**
     * @param CallbackErrorEvent $event
     */
    public function onError(CallbackErrorEvent $event)
    {
        $this->handleEvent($event);
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    protected function handleEvent(AbstractCallbackEvent $event)
    {
        $data = $this->encoder->decode($event->getQueryString());
        $response = new Response($data);

        $paymentTransaction = $event->getPaymentTransaction();
        $paymentTransactionData = $this->encoder->decode($paymentTransaction->getData());

        $keys = [Option\SecureToken::SECURETOKEN, Option\SecureTokenIdentifier::SECURETOKENID];
        $keys = array_flip($keys);
        if (array_intersect_key($data, $keys) !== array_intersect_key($paymentTransactionData, $keys)) {
            return;
        }

        $paymentTransaction
            ->setState($response->getState())
            ->setReference($response->getReference())
            ->setData($event->getQueryString());
    }
}

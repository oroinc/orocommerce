<?php

namespace OroB2B\Bundle\PaymentBundle\EventListener\Callback;

use Psr\Log\LoggerAwareTrait;

use Symfony\Component\HttpFoundation\Session\Session;

use OroB2B\Bundle\PaymentBundle\Event\AbstractCallbackEvent;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\ResponseStatusMap;

class PayflowListener
{
    use LoggerAwareTrait;
    
    /** @var Session */
    protected $session;

    /** @var PaymentMethodRegistry */
    protected $paymentMethodRegistry;

    /**
     * @param Session $session
     * @param PaymentMethodRegistry $paymentMethodRegistry
     */
    public function __construct(Session $session, PaymentMethodRegistry $paymentMethodRegistry)
    {
        $this->session = $session;
        $this->paymentMethodRegistry = $paymentMethodRegistry;
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onError(AbstractCallbackEvent $event)
    {
        $eventData = $event->getData();
        $response = new Response($eventData);

        if (in_array($response->getResult(), [ResponseStatusMap::SECURE_TOKEN_EXPIRED], true)) {
            $this->session->getFlashBag()->set('warning', 'orob2b.payment.result.token_expired');
        }
    }

    /**
     * @param AbstractCallbackEvent $event
     */
    public function onNotify(AbstractCallbackEvent $event)
    {
        $paymentTransaction = $event->getPaymentTransaction();

        if (!$paymentTransaction || $paymentTransaction->getReference()) {
            return;
        }

        $data = $event->getData();

        $paymentTransaction
            ->setResponse(array_replace($paymentTransaction->getResponse(), $data));

        try {
            $this->paymentMethodRegistry
                ->getPaymentMethod($paymentTransaction->getPaymentMethod())
                ->execute(PayflowGateway::COMPLETE, $paymentTransaction);
            $event->markSuccessful();
        } catch (\InvalidArgumentException $e) {
            if ($this->logger) {
                // do not expose sensitive data in context
                $this->logger->error($e->getMessage(), []);
            }
        }
    }
}

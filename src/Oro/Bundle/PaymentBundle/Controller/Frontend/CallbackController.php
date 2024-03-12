<?php

namespace Oro\Bundle\PaymentBundle\Controller\Frontend;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackHandler;
use Oro\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use Oro\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Serves callback actions.
 */
class CallbackController extends AbstractController
{
    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Request $request
     * @return Response
     */
    #[Route(
        path: '/return/{accessIdentifier}',
        name: 'oro_payment_callback_return',
        requirements: ['accessIdentifier' => '[a-zA-Z0-9\-]+'],
        methods: ['GET', 'POST']
    )]
    #[ParamConverter('paymentTransaction', options: ['mapping' => ['accessIdentifier' => 'accessIdentifier']])]
    public function callbackReturnAction(PaymentTransaction $paymentTransaction, Request $request)
    {
        $event = new CallbackReturnEvent($request->request->all() + $request->query->all());
        $event->setPaymentTransaction($paymentTransaction);

        return $this->container->get(CallbackHandler::class)->handle($event);
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @param Request $request
     * @return Response
     */
    #[Route(
        path: '/error/{accessIdentifier}',
        name: 'oro_payment_callback_error',
        requirements: ['accessIdentifier' => '[a-zA-Z0-9\-]+'],
        methods: ['GET', 'POST']
    )]
    #[ParamConverter('paymentTransaction', options: ['mapping' => ['accessIdentifier' => 'accessIdentifier']])]
    public function callbackErrorAction(PaymentTransaction $paymentTransaction, Request $request)
    {
        $event = new CallbackErrorEvent($request->request->all() + $request->query->all());
        $event->setPaymentTransaction($paymentTransaction);

        return $this->container->get(CallbackHandler::class)->handle($event);
    }

    /**
     * @param Request $request
     * @param PaymentTransaction $paymentTransaction
     * @return Response
     */
    #[Route(
        path: '/notify/{accessIdentifier}/{accessToken}',
        name: 'oro_payment_callback_notify',
        requirements: ['accessIdentifier' => '[a-zA-Z0-9\-]+', 'accessToken' => '[a-zA-Z0-9\-]+'],
        methods: ['POST']
    )]
    #[ParamConverter(
        'paymentTransaction',
        options: ['mapping' => ['accessIdentifier' => 'accessIdentifier', 'accessToken' => 'accessToken']]
    )]
    public function callbackNotifyAction(PaymentTransaction $paymentTransaction, Request $request)
    {
        $event = new CallbackNotifyEvent($request->request->all());
        $event->setPaymentTransaction($paymentTransaction);

        return $this->container->get(CallbackHandler::class)->handle($event);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                CallbackHandler::class,
            ]
        );
    }
}

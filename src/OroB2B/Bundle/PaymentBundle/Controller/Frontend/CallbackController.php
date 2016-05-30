<?php

namespace OroB2B\Bundle\PaymentBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;

class CallbackController extends Controller
{
    /**
     * @Route(
     *     "/return/{accessIdentifier}",
     *     name="orob2b_payment_callback_return",
     *     requirements={"accessIdentifier"="[a-zA-Z0-9_-]+"}
     * )
     * @ParamConverter("paymentTransaction", options={"accessIdentifier" = "accessIdentifier"})
     * @Method({"GET", "POST"})
     * @param PaymentTransaction $paymentTransaction
     * @param Request $request
     * @return Response
     */
    public function callbackReturnAction(PaymentTransaction $paymentTransaction, Request $request)
    {
        $event = new CallbackReturnEvent($request->request->all() + $request->query->all());
        $event->setPaymentTransaction($paymentTransaction);

        return $this->get('orob2b_payment.event.callback_handler')->handle($event);
    }

    /**
     * @Route(
     *     "/error/{accessIdentifier}",
     *     name="orob2b_payment_callback_error",
     *     requirements={"accessIdentifier"="[a-zA-Z0-9_-]+"}
     * )
     * @ParamConverter("paymentTransaction", options={"accessIdentifier" = "accessIdentifier"})
     * @Method({"GET", "POST"})
     * @param PaymentTransaction $paymentTransaction
     * @param Request $request
     * @return Response
     */
    public function callbackErrorAction(PaymentTransaction $paymentTransaction, Request $request)
    {
        $event = new CallbackErrorEvent($request->request->all() + $request->query->all());
        $event->setPaymentTransaction($paymentTransaction);

        return $this->get('orob2b_payment.event.callback_handler')->handle($event);
    }

    /**
     * @Route(
     *     "/notify/{paymentMethodName}",
     *     name="orob2b_payment_callback_notify",
     *     requirements={"paymentMethodName"="\w+"}
     * )
     * @Method({"POST"})
     * @param Request $request
     * @param string $paymentMethodName
     * @return Response
     */
    public function callbackNotifyAction(Request $request, $paymentMethodName)
    {
        $event = new CallbackNotifyEvent($request->request->all());

        try {
            $paymentMethod = $this->get('orob2b_payment.payment_method.registry')->getPaymentMethod($paymentMethodName);
            $event->setPaymentMethod($paymentMethod);
        } catch (\InvalidArgumentException $e) {
            return $event->getResponse();
        }

        return $this->get('orob2b_payment.event.callback_handler')->handle($event);
    }
}

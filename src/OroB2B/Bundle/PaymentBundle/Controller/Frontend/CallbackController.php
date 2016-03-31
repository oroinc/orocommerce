<?php

namespace OroB2B\Bundle\PaymentBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class CallbackController extends Controller
{
    /**
     * @Route("/return/{transactionId}", name="orob2b_payment_callback_return", requirements={"id"="\d+"})
     * @param string $transactionId
     * @param Request $request
     * @return Response
     */
    public function callbackReturnAction($transactionId, Request $request)
    {
        $event = new CallbackReturnEvent($request->getQueryString());

        return $this->get('orob2b_payment.eveny.callback_handler')->handle($transactionId, $event);
    }

    /**
     * @Route("/error/{transactionId}", name="orob2b_payment_callback_error", requirements={"type"="\w+", "id"="\d+"})
     * @param string $transactionId
     * @param Request $request
     * @return Response
     */
    public function callbackErrorAction($transactionId, Request $request)
    {
        $event = new CallbackErrorEvent($request->getQueryString());

        return $this->get('orob2b_payment.eveny.callback_handler')->handle($transactionId, $event);
    }
}

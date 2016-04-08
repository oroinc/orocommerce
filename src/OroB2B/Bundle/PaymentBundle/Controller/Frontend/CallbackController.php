<?php

namespace OroB2B\Bundle\PaymentBundle\Controller\Frontend;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use OroB2B\Bundle\PaymentBundle\Event\CallbackErrorEvent;
use OroB2B\Bundle\PaymentBundle\Event\CallbackReturnEvent;

class CallbackController extends Controller
{
    /**
     * @Route(
     *     "/return/{accessIdentifier}/{accessToken}",
     *     name="orob2b_payment_callback_return",
     *     requirements={"accessIdentifier"="\w+", "accessToken"="\w+"}
     * )
     * @Method({"GET", "POST"})
     * @param string $accessIdentifier
     * @param string $accessToken
     * @param Request $request
     * @return Response
     */
    public function callbackReturnAction($accessIdentifier, $accessToken, Request $request)
    {
        $event = new CallbackReturnEvent($request->request->all() + $request->query->all());

        return $this->get('orob2b_payment.event.callback_handler')->handle($accessIdentifier, $accessToken, $event);
    }

    /**
     * @Route(
     *     "/error/{accessIdentifier}/{accessToken}",
     *     name="orob2b_payment_callback_error",
     *     requirements={"accessIdentifier"="\w+", "accessToken"="\w+"}
     * )
     * @Method({"GET", "POST"})
     * @param string $accessIdentifier
     * @param string $accessToken
     * @param Request $request
     * @return Response
     */
    public function callbackErrorAction($accessIdentifier, $accessToken, Request $request)
    {
        $event = new CallbackErrorEvent($request->request->all() + $request->query->all());

        return $this->get('orob2b_payment.event.callback_handler')->handle($accessIdentifier, $accessToken, $event);
    }
}

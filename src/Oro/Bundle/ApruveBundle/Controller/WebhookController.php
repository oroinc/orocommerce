<?php

namespace Oro\Bundle\ApruveBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/webhook")
 */
class WebhookController extends Controller
{
    /**
     * @Route("/notify/{token}", name="oro_apruve_webhook_notify", options={"expose"=true})
     * @Method("POST")
     *
     * @param string $token
     * @param Request $request
     *
     * @return Response
     */
    public function notifyAction($token, Request $request)
    {
        // todo@webevt: add proper implementation.
        return new Response();
    }
}

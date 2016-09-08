<?php

namespace Oro\Bundle\FrontendBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

use FOS\RestBundle\Controller\ExceptionController as BaseExceptionController;

class ExceptionController extends BaseExceptionController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, $exception, DebugLoggerInterface $logger = null)
    {
        if ($this->isLayoutRendering($request)) {
            $container = $this->container;
            $code = $this->getStatusCode($exception);
            $text = $this->getStatusText($code);
            $url = $container->get('router')->generate('orob2b_frontend_exception', ['code' => $code, 'text' => $text]);
            return $container->get('kernel')->handle(Request::create($url));
        } else {
            return parent::showAction($request, $exception, $logger);
        }
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isLayoutRendering(Request $request)
    {
        return $this->container->get('oro_frontend.request.frontend_helper')->isFrontendRequest($request)
            && $request->getRequestFormat() === 'html'
            && !$this->showException($request);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    protected function showException(Request $request)
    {
        return $request->attributes->get('showException', $this->container->get('kernel')->isDebug());
    }

    /**
     * @param $code
     * @return string
     */
    protected function getStatusText($code)
    {
        return array_key_exists($code, Response::$statusTexts) ? Response::$statusTexts[$code] : "error";
    }
}

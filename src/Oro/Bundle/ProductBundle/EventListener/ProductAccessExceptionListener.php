<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductAccessExceptionListener
{
    const PRODUCT_VIEW_ROUTE = 'oro_product_frontend_product_view';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onAccessException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if (!$exception instanceof AccessDeniedHttpException) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request->isXmlHttpRequest() ||
            $request->attributes->get('_route') !== self::PRODUCT_VIEW_ROUTE) {
            return;
        }

        // replace the 403 with 404 here
        $newException = new NotFoundHttpException($exception->getMessage());

        $event->setException($newException);
    }
}

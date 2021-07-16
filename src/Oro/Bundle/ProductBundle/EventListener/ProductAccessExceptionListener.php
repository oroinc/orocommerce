<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

/**
 * Replace 403 with 404 when access denied for oro_product_frontend_product_view route.
 */
class ProductAccessExceptionListener
{
    const PRODUCT_VIEW_ROUTE = 'oro_product_frontend_product_view';

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function onAccessException(ExceptionEvent $event)
    {
        $exception = $event->getThrowable();

        if (!($exception instanceof AccessDeniedException ||
            $exception instanceof AccessDeniedHttpException ||
            $exception instanceof InsufficientAuthenticationException)) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request
            || $request->isXmlHttpRequest()
            || $request->attributes->get('_route') !== self::PRODUCT_VIEW_ROUTE
        ) {
            return;
        }

        // replace the 403 with 404 here.
        // Do not set previous exception here, as it will be processed by the
        // Symfony\Component\Security\Http\Firewall\ExceptionListener::onKernelException and will be processed
        // as 403 error again.
        $newException = new NotFoundHttpException($exception->getMessage());

        $event->setThrowable($newException);
    }
}

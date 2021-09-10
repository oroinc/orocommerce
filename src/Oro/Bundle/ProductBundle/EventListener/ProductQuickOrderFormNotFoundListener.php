<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Responsible for redirecting to the login page if client do not have access to the quick order form page.
 */
class ProductQuickOrderFormNotFoundListener
{
    private UrlGeneratorInterface $urlGenerator;
    private TokenAccessorInterface $tokenAccessor;

    public function __construct(UrlGeneratorInterface $urlGenerator, TokenAccessorInterface $tokenAccessor)
    {
        $this->urlGenerator = $urlGenerator;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$this->isSupported($event)) {
            return;
        }

        $request = $event->getRequest();
        $request->getSession()->set('_security.frontend.target_path', $request->getUri());
        $url = $this->urlGenerator->generate('oro_customer_customer_user_security_login');
        $event->setResponse(new RedirectResponse($url));
    }

    private function isSupported(ExceptionEvent $event): bool
    {
        $request = $event->getRequest();
        $route = $request->get('_route');

        return 'oro_product_frontend_quick_add' === $route
            && $event->getThrowable() instanceof NotFoundHttpException
            && $this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken;
    }
}

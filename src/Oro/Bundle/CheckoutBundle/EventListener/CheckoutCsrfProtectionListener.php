<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * "Double submit cookie" checkout verification.
 *
 * This class is responsible for the security of the checkout, since csrf was disabled in
 * Oro\Bundle\CheckoutBundle\Layout\DataProvider\TransitionFormProvider,
 * so it was possible to continue the checkout without reloading the page after the customer user authorized
 * in another tab/window, etc.
 */
class CheckoutCsrfProtectionListener
{
    public function __construct(
        private CsrfRequestManager $csrfRequestManager,
        private RequestStack $requestStack
    ) {
    }

    public function onTransitionBefore(CheckoutTransitionBeforeEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return;
        }

        if ($this->csrfRequestManager->isRequestTokenValid($request)) {
            return;
        }

        throw new AccessDeniedHttpException('Invalid CSRF token');
    }
}

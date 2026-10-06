<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Applies the #[CsrfProtection] rule of the controller action on the sub-request that renders an embedded fragment. The
 * platform applies it on main requests only.
 */
class EmbeddedFragmentCsrfProtectionListener
{
    public function __construct(
        private readonly CsrfRequestManager $csrfRequestManager,
    ) {
    }

    /**
     * @throws AccessDeniedHttpException when the target action requires a CSRF token and the request has no valid one
     */
    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE)) {
            return;
        }

        $csrfProtection = $request->attributes->get('_' . CsrfProtection::ALIAS_NAME);
        if (!$csrfProtection instanceof CsrfProtection || !$csrfProtection->isEnabled()) {
            return;
        }

        if (!$this->csrfRequestManager->isRequestTokenValid($request, $csrfProtection->isUseRequest())) {
            throw new AccessDeniedHttpException('Invalid CSRF token');
        }
    }
}

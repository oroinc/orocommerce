<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\UIBundle\Provider\ControllerClassProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Applies to an embedded fragment what the platform applies to a main request of the target page but skips
 * for a sub-request: the feature toggle of the route, the ACL of the controller action and the CSRF protection
 * the action declares. Without it a fragment would run a page the caller may not open directly.
 */
class EmbeddedFragmentAccessChecker
{
    private const string ROUTES_RESOURCE_TYPE = 'routes';
    private const string INVALID_CSRF_TOKEN_MESSAGE = 'Invalid CSRF token';

    public function __construct(
        private readonly FeatureChecker $featureChecker,
        private readonly ControllerClassProvider $controllerClassProvider,
        private readonly ClassAuthorizationChecker $classAuthorizationChecker,
        private readonly CsrfRequestManager $csrfRequestManager,
    ) {
    }

    /**
     * @throws NotFoundHttpException     when the target page is disabled or has no controller
     * @throws AccessDeniedException     when the caller may not open the target page
     * @throws AccessDeniedHttpException when a state-changing request misses the CSRF token the target requires
     */
    public function denyAccessUnlessGranted(EmbeddedFragmentTarget $target, Request $request): void
    {
        if (!$this->featureChecker->isResourceEnabled($target->routeName, self::ROUTES_RESOURCE_TYPE)) {
            throw new NotFoundHttpException(
                sprintf('The page behind embedded block "%s" is disabled.', $target->block)
            );
        }

        [$controllerClass, $controllerMethod] = $this->resolveController($target);
        if (!$this->classAuthorizationChecker->isClassMethodGranted($controllerClass, $controllerMethod)) {
            throw new AccessDeniedException(
                sprintf('Access denied to the page behind embedded block "%s".', $target->block)
            );
        }

        if (!$request->isMethod(Request::METHOD_GET)) {
            $this->denyUnlessCsrfTokenIsValid($request, $controllerClass, $controllerMethod);
        }
    }

    /**
     * @return array{0: class-string, 1: string} the controller class and the action method of the target route
     */
    private function resolveController(EmbeddedFragmentTarget $target): array
    {
        $controller = $this->controllerClassProvider->getControllers()[$target->routeName] ?? null;
        if (null === $controller) {
            throw new NotFoundHttpException(
                sprintf('Cannot resolve the controller behind embedded block "%s".', $target->block)
            );
        }

        return $controller;
    }

    /**
     * Mirrors CsrfProtectionRequestListener, which validates the #[CsrfProtection] attribute of a main request only.
     */
    private function denyUnlessCsrfTokenIsValid(Request $request, string $controllerClass, string $controllerMethod): void
    {
        $csrfProtection = $this->resolveCsrfProtection($controllerClass, $controllerMethod);
        if (
            $csrfProtection?->isEnabled()
            && !$this->csrfRequestManager->isRequestTokenValid($request, $csrfProtection->isUseRequest())
        ) {
            throw new AccessDeniedHttpException(self::INVALID_CSRF_TOKEN_MESSAGE);
        }
    }

    private function resolveCsrfProtection(string $controllerClass, string $controllerMethod): ?CsrfProtection
    {
        $attributes = (new \ReflectionMethod($controllerClass, $controllerMethod))->getAttributes(CsrfProtection::class)
            ?: (new \ReflectionClass($controllerClass))->getAttributes(CsrfProtection::class);

        return $attributes ? $attributes[0]->newInstance() : null;
    }
}

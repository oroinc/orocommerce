<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Enforces the ACL of the controller action on the sub-request that renders an embedded fragment. The platform enforces
 * it on main requests only.
 */
class EmbeddedFragmentAccessListener
{
    public function __construct(
        private readonly ClassAuthorizationChecker $classAuthorizationChecker,
    ) {
    }

    /**
     * @throws AccessDeniedException when the fragment target action is not granted
     */
    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $attributes = $event->getRequest()->attributes;
        if (
            !$attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE)
            || true === $attributes->get('_oro_access_checked')
        ) {
            return;
        }

        $controllerAction = $this->resolveControllerAction($event->getController());
        if (null === $controllerAction) {
            return;
        }

        [$controllerClass, $controllerMethod] = $controllerAction;
        if (!$this->classAuthorizationChecker->isClassMethodGranted($controllerClass, $controllerMethod)) {
            throw new AccessDeniedException(
                sprintf('Access denied to %s::%s.', $controllerClass, $controllerMethod)
            );
        }
    }

    /**
     * Resolves the class and method the same way ControllerListener::checkControllerActionAccess does: a Closure is
     * an invokable object, so it is checked as Closure::__invoke.
     *
     * @return array{0: string, 1: string}|null the controller class and the action method
     */
    private function resolveControllerAction(callable $controller): ?array
    {
        if (\is_array($controller)) {
            [$object, $method] = $controller;

            return [ClassUtils::getRealClass($object), $method];
        }

        if (\is_object($controller) && method_exists($controller, '__invoke')) {
            return [ClassUtils::getRealClass($controller), '__invoke'];
        }

        return null;
    }
}

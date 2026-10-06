<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\EventListener;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend\EmbeddedFragmentController;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns a 404 for the sub-request of an embedded fragment when the feature of the target route is disabled, as for a
 * main request.
 */
class EmbeddedFragmentFeatureToggleListener
{
    public function __construct(
        private readonly FeatureChecker $featureChecker,
    ) {
    }

    /**
     * @throws NotFoundHttpException when the target page is disabled
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$request->attributes->get(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE)) {
            return;
        }

        $route = $request->attributes->get('_route');
        if (!\is_string($route)) {
            return;
        }

        if (!$this->featureChecker->isResourceEnabled($route, 'routes')) {
            throw new NotFoundHttpException(
                sprintf(
                    'The page behind embedded block "%s" is disabled.',
                    $request->attributes->get(EmbeddedFragmentController::BLOCK_ATTRIBUTE)
                )
            );
        }
    }
}

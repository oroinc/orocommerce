<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Request;

use Oro\Bundle\StorefrontEmbeddedBundle\Event\EmbeddedFragmentResolveEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Resolves the URL of the page an embedded fragment lives on, with the layout block IDs the fragment renders.
 */
class EmbeddedFragmentUrlResolver
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly EmbeddedFragmentRegistry $embeddedFragmentRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param array<string|int, mixed> $routeParameters
     * @param array<string, mixed> $context
     *
     * @throws NotFoundHttpException
     * @throws BadRequestHttpException
     * @throws \LogicException When the listeners leave the fragment without block IDs.
     */
    public function resolveFragmentUrl(string $fragmentName, array $routeParameters = [], array $context = []): string
    {
        $routeName = $this->resolveRouteName($fragmentName);

        $event = new EmbeddedFragmentResolveEvent(
            $fragmentName,
            $routeName,
            $routeParameters,
            $context,
            $this->embeddedFragmentRegistry->getLayoutBlockIds($fragmentName)
        );
        $this->eventDispatcher->dispatch($event);

        $blockIds = $event->getBlockIds();
        if (!$blockIds) {
            // The router drops an empty layout_block_ids parameter, and the target page would render in full.
            throw new \LogicException(
                sprintf('The listeners left the embedded fragment "%s" without layout block ids.', $fragmentName)
            );
        }

        $routeParameters = $event->getRouteParameters();
        $routeParameters['layout_block_ids'] = $blockIds;

        return $this->generateUrl($fragmentName, $routeName, $routeParameters);
    }

    private function resolveRouteName(string $fragmentName): string
    {
        if (!$this->embeddedFragmentRegistry->hasFragment($fragmentName)) {
            throw new NotFoundHttpException('Unknown embedded fragment.');
        }

        return $this->embeddedFragmentRegistry->getRouteName($fragmentName);
    }

    /**
     * @param array<string|int, mixed> $routeParameters
     */
    private function generateUrl(string $fragmentName, string $routeName, array $routeParameters): string
    {
        try {
            return $this->router->generate($routeName, $routeParameters);
        } catch (MissingMandatoryParametersException | InvalidParameterException $exception) {
            throw new BadRequestHttpException(
                sprintf('Invalid parameters for embedded fragment "%s".', $fragmentName),
                $exception
            );
        } catch (RouteNotFoundException $exception) {
            throw new NotFoundHttpException(
                sprintf('Embedded fragment "%s" points at an unknown route.', $fragmentName),
                $exception
            );
        }
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Renders the block of an embedded fragment through an internal sub-request to its target page.
 */
class EmbeddedFragmentRenderer
{
    public function __construct(
        private readonly HttpKernelInterface $httpKernel,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    public function render(Request $request, EmbeddedFragmentTarget $target): Response
    {
        $subRequest = $request->duplicate(
            ['layout_block_ids' => $this->resolveBlockIdsToRender($request, $target->block)]
                + array_diff_key($target->routeParameters, $target->attributes),
            null,
            $target->attributes + $this->getInheritedAttributes($request)
        );
        // Set after the merge, so that neither the target nor the inherited attributes can override the marker.
        $subRequest->attributes->set(EmbeddedContextConfigurator::REQUEST_ATTRIBUTE, true);
        $subRequest->attributes->set('_route_params', $this->resolveRouteParams($target));
        $subRequest->server->set('REQUEST_URI', $target->url);
        $subRequest->server->set(
            'QUERY_STRING',
            (string) parse_url($target->url, PHP_URL_QUERY)
        );

        return $this->httpKernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
    }

    /**
     * @return string[]
     */
    private function resolveBlockIdsToRender(Request $request, string $block): array
    {
        $blockIds = [$block];

        if (!$this->embeddedRequestChecker->isEmbeddedRequestReload()) {
            $blockIds[] = 'layout_js_modules_config';
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            $blockIds[] = 'title';
        }

        return $blockIds;
    }

    /**
     * @return array<string, mixed> the attributes of the main request without its controller (the fragment endpoint)
     */
    private function getInheritedAttributes(Request $request): array
    {
        $attributes = $request->attributes->all();
        unset($attributes['_controller']);

        return $attributes;
    }

    /**
     * @return array<string, mixed> the variables that the router matched for the target url, without the routing
     * internals
     */
    private function resolveRouteParams(EmbeddedFragmentTarget $target): array
    {
        $routeParams = $target->attributes;
        unset($routeParams['_route'], $routeParams['_controller']);

        return $routeParams;
    }
}

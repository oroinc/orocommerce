<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Fragment;

use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Renders the block of an embedded fragment through an internal sub-request of the target page. The sub-request
 * carries the caller's session, cookies, headers and body, asks the layout engine for the block subtree only
 * (`layout_block_ids[]`, plus the config scripts block on the first render of a fragment and the page title block
 * on a GET) and runs with the attributes the router matched for the target url on top of the main request's own,
 * the way the platform builds its slug sub-requests. Exceptions of the target propagate to the main request's exception handling.
 */
class EmbeddedFragmentRenderer
{
    private const string LAYOUT_BLOCK_IDS_PARAMETER = 'layout_block_ids';
    private const string CONFIG_SCRIPTS_BLOCK_ID = 'layout_js_modules_config';
    private const string TITLE_BLOCK_ID = 'title';
    private const string ROUTE_ATTRIBUTE = '_route';
    private const string CONTROLLER_ATTRIBUTE = '_controller';
    private const string ROUTE_PARAMS_ATTRIBUTE = '_route_params';
    private const string REQUEST_URI_SERVER_PARAMETER = 'REQUEST_URI';
    private const string QUERY_STRING_SERVER_PARAMETER = 'QUERY_STRING';

    public function __construct(
        private readonly HttpKernelInterface $httpKernel,
        private readonly EmbeddedRequestChecker $embeddedRequestChecker,
    ) {
    }

    public function render(Request $request, EmbeddedFragmentTarget $target): Response
    {
        $subRequest = $request->duplicate(
            [self::LAYOUT_BLOCK_IDS_PARAMETER => $this->resolveBlockIdsToRender($request, $target->block)]
                + array_diff_key($target->routeParameters, $target->attributes),
            null,
            $target->attributes
                + [EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true]
                + $request->attributes->all()
        );
        $subRequest->attributes->set(self::ROUTE_PARAMS_ATTRIBUTE, $this->resolveRouteParams($target));
        $subRequest->server->set(self::REQUEST_URI_SERVER_PARAMETER, $target->url);
        $subRequest->server->set(
            self::QUERY_STRING_SERVER_PARAMETER,
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

        if (!$this->embeddedRequestChecker->isFragmentReload()) {
            $blockIds[] = self::CONFIG_SCRIPTS_BLOCK_ID;
        }

        if ($request->isMethod(Request::METHOD_GET)) {
            $blockIds[] = self::TITLE_BLOCK_ID;
        }

        return $blockIds;
    }

    /**
     * @return array<string, mixed> the variables the router matched for the target url, without routing internals
     */
    private function resolveRouteParams(EmbeddedFragmentTarget $target): array
    {
        $routeParams = $target->attributes;
        unset($routeParams[self::ROUTE_ATTRIBUTE], $routeParams[self::CONTROLLER_ATTRIBUTE]);

        return $routeParams;
    }
}

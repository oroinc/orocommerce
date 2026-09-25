<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentAccessChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentRenderer;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentResponseDecorator;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTargetResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves a curated embedded layout block by id, so an external consumer (e.g. the Nuxt storefront) can
 * request a storefront fragment without knowing which page URL it lives on. The block is resolved to its
 * storefront page, the access rules of that page are checked here because the platform enforces them only
 * for a main request, and the page is rendered through an internal sub-request with `layout_block_ids[]`
 * (LayoutListener then renders only that subtree as JSON) that runs with the caller's session. POST forwards
 * a form submit the same way, so the response is either the re-rendered block (validation errors) or the
 * target's save redirect.
 *
 * No #[Acl] on purpose: access is decided per target page by EmbeddedFragmentAccessChecker.
 */
class EmbeddedFragmentController extends AbstractController
{
    public const string ROUTE_NAME = 'oro_storefront_embedded_fragment';
    public const string BLOCK_ID_REQUIREMENT = 'embedded_\w+';

    #[Route(
        path: '/{block}',
        name: self::ROUTE_NAME,
        requirements: ['block' => self::BLOCK_ID_REQUIREMENT],
        methods: ['GET', 'POST']
    )]
    public function fragmentAction(string $block, Request $request): Response
    {
        $target = $this->container->get(EmbeddedFragmentTargetResolver::class)->resolve($block, $request);
        $this->container->get(EmbeddedFragmentAccessChecker::class)->denyAccessUnlessGranted($target, $request);

        $response = $this->container->get(EmbeddedFragmentRenderer::class)->render($request, $target);
        $this->container->get(EmbeddedFragmentResponseDecorator::class)->decorate($request, $response, $target);

        return $response;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            EmbeddedFragmentTargetResolver::class,
            EmbeddedFragmentAccessChecker::class,
            EmbeddedFragmentRenderer::class,
            EmbeddedFragmentResponseDecorator::class,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentRenderer;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentResponseDecorator;
use Oro\Bundle\StorefrontEmbeddedBundle\Fragment\EmbeddedFragmentTargetResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Renders a curated embedded layout block by its id, so an external consumer can get a storefront fragment without its
 * page URL.
 *
 * No #[Acl]: the fragment listeners apply the ACL of each target page.
 * No #[CsrfProtection]: the sub-request copies this attribute, so it applies to every target page.
 */
final class EmbeddedFragmentController extends AbstractController
{
    public const string ROUTE_NAME = 'oro_storefront_embedded_fragment';
    public const string BLOCK_ID_REQUIREMENT = 'embedded_\w+';
    public const string BLOCK_ATTRIBUTE = 'block';

    #[Route(
        path: '/{block}',
        name: self::ROUTE_NAME,
        requirements: [self::BLOCK_ATTRIBUTE => self::BLOCK_ID_REQUIREMENT],
        methods: ['GET', 'POST']
    )]
    public function fragmentAction(string $block, Request $request): Response
    {
        $target = $this->container->get(EmbeddedFragmentTargetResolver::class)->resolve($block, $request);

        $response = $this->container->get(EmbeddedFragmentRenderer::class)->render($request, $target);
        $this->container->get(EmbeddedFragmentResponseDecorator::class)->decorate($request, $response, $target);

        return $response;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            EmbeddedFragmentTargetResolver::class,
            EmbeddedFragmentRenderer::class,
            EmbeddedFragmentResponseDecorator::class,
        ];
    }
}

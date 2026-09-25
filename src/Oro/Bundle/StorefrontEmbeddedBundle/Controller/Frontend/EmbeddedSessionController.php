<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\StorefrontEmbeddedBundle\Security\EmbeddedSessionOpener;
use Oro\Bundle\StorefrontEmbeddedBundle\Security\StorefrontAccessTokenResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Bootstraps a storefront session from an OAuth2 access token, so a host on a different origin (where the
 * browser cannot forward Oro's cookies) can still render session-based embedded fragments: the host calls this
 * once with the visitor's Bearer token and reuses the returned cookies server-side. The token is resolved by
 * StorefrontAccessTokenResolver and the session is opened by EmbeddedSessionOpener; the action is only the
 * HTTP adapter around them.
 *
 * No #[Acl] on purpose: this action performs the authentication, access is decided by the validated token.
 */
class EmbeddedSessionController
{
    public const string ROUTE_NAME = 'oro_storefront_embedded_session';

    public function __construct(
        private readonly StorefrontAccessTokenResolver $accessTokenResolver,
        private readonly EmbeddedSessionOpener $sessionOpener,
    ) {
    }

    #[Route(path: '', name: self::ROUTE_NAME, methods: ['POST'])]
    public function bootstrapAction(Request $request): Response
    {
        $this->sessionOpener->open($request, $this->accessTokenResolver->resolve($request));

        return $this->createNoStoreResponse();
    }

    /**
     * The response carries credentials (a session cookie or a visitor cookie), so no cache may keep it.
     */
    private function createNoStoreResponse(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}

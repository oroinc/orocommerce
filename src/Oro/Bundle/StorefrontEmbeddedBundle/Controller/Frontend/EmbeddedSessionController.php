<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The storefront GET target of the OAuth2 session transfer that opens an embedded session.
 *
 * No #[Acl]: the action returns no data.
 */
final class EmbeddedSessionController extends AbstractController
{
    #[Route(path: '/session', name: 'oro_storefront_embedded_session', methods: ['GET'])]
    public function sessionAction(): Response
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        // A cache must never keep a response to a request with session cookies.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\StorefrontEmbeddedBundle\Form\Handler\EmbeddedFragmentPreferencesHandler;
use Oro\Bundle\StorefrontEmbeddedBundle\Form\Type\EmbeddedFragmentPreferencesType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Stores the localization and the currency of the current embedded session.
 *
 * No #[Acl]: the action changes only the preferences of the caller.
 */
final class EmbeddedPreferencesController extends AbstractController
{
    #[Route(path: '/preferences', name: 'oro_storefront_embedded_preferences', methods: ['POST'])]
    #[CsrfProtection]
    public function preferencesAction(Request $request): Response
    {
        if (!$request->hasPreviousSession()) {
            return $this->createResponse(Response::HTTP_BAD_REQUEST);
        }

        $form = $this->container->get('form.factory')->createNamed(
            // The empty name makes the handler submit the top-level fields of the request.
            '',
            EmbeddedFragmentPreferencesType::class,
            null,
            // CSRF is checked by the #[CsrfProtection] attribute of the action.
            ['csrf_protection' => false]
        );
        if (!$this->container->get(EmbeddedFragmentPreferencesHandler::class)->process(null, $form, $request)) {
            return $this->createResponse(Response::HTTP_BAD_REQUEST);
        }

        return $this->createResponse(Response::HTTP_NO_CONTENT);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            EmbeddedFragmentPreferencesHandler::class,
        ];
    }

    private function createResponse(int $statusCode): Response
    {
        $response = new Response('', $statusCode);
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}

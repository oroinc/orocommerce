<?php

namespace Oro\Bundle\ShippingBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\ShippingBundle\Manager\MultiShippingIntegrationManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing multi shipping integration
 */
class MultiShippingController extends AbstractController
{
    /**
     * @Route(
     *     "/create-multishipping-integration",
     *     name="oro_shipping_create_multishipping_integration",
     *     methods={"POST"}
     * )
     * @CsrfProtection()
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createMultishippingIntegrationAction(Request $request): JsonResponse
    {
        $error = null;

        /** @var MultiShippingIntegrationManager $multiShippingIntegrationManager */
        $multiShippingIntegrationManager = $this->get(MultiShippingIntegrationManager::class);
        $translator = $this->get(TranslatorInterface::class);

        try {
            $multiShippingIntegrationManager->createIntegration();
        } catch (AccessDeniedException $e) {
            $error = $translator->trans(
                'oro.shipping.multi_shipping_method.settings.create_integration.not_authorized.error'
            );
        }

        return new JsonResponse($error ?? '');
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                MultiShippingIntegrationManager::class,
                TranslatorInterface::class,
            ]
        );
    }
}

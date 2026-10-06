<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedThemeAssetsProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Lists the built theme files that an embedded host can copy into its public directory.
 */
final class EmbeddedThemeAssetsController extends AbstractController
{
    #[Route(path: '/theme-assets', name: 'oro_storefront_embedded_theme_assets', methods: ['GET'])]
    public function themeAssetsAction(): JsonResponse
    {
        return new JsonResponse([
            'files' => $this->container->get(EmbeddedThemeAssetsProvider::class)->getFiles()
        ]);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            EmbeddedThemeAssetsProvider::class,
        ];
    }
}

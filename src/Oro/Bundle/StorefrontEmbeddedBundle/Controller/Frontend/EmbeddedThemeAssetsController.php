<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Controller\Frontend;

use Oro\Bundle\StorefrontEmbeddedBundle\Provider\EmbeddedThemeAssetsProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Publishes the built theme files an embedded host may dump into its own public directory. Deliberately open:
 * every path it names is a static file the web server already serves to anyone, and it names nothing outside
 * the build directory.
 */
class EmbeddedThemeAssetsController
{
    public const string ROUTE_NAME = 'oro_storefront_embedded_theme_assets';

    private const string FILES_KEY = 'files';

    public function __construct(private readonly EmbeddedThemeAssetsProvider $themeAssetsProvider)
    {
    }

    #[Route(path: '/theme-assets', name: self::ROUTE_NAME, methods: ['GET'])]
    public function themeAssetsAction(): JsonResponse
    {
        return new JsonResponse([self::FILES_KEY => $this->themeAssetsProvider->getFiles()]);
    }
}

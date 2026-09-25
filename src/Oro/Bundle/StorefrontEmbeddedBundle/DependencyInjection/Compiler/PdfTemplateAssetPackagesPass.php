<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Makes PDF template rendering resolve Oro's own assets through the plain asset packages instead of the embedded
 * ones. A PDF template never reaches a browser: the url an "asset" call returns is used to read the file from disk
 * and is replaced in the markup by the name the file is sent to the PDF engine under. Prefixing it with Oro's origin
 * for an embedded host turns that read into an HTTP request, whose stream cannot be rewound, and the PDF engine then
 * receives an empty document.
 *
 * The plain packages are the inner service of the embedded decorator, which must therefore stay the innermost
 * decorator of `assets.packages` (see its decoration priority in services.yml). The argument is bound by name, so
 * a reordered constructor of the collector fails at compile time instead of at PDF rendering time.
 */
class PdfTemplateAssetPackagesPass implements CompilerPassInterface
{
    private const string PDF_TEMPLATE_ASSETS_COLLECTOR =
        'oro_pdf_generator.pdf_template_renderer.twig.pdf_template_assets_collector';
    private const string PACKAGES_ARGUMENT = '$packages';
    private const string PLAIN_ASSET_PACKAGES = 'oro_storefront_embedded.asset.embedded_request_asset_packages.inner';

    #[\Override]
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::PDF_TEMPLATE_ASSETS_COLLECTOR)) {
            return;
        }

        $container->getDefinition(self::PDF_TEMPLATE_ASSETS_COLLECTOR)
            ->setArgument(self::PACKAGES_ARGUMENT, new Reference(self::PLAIN_ASSET_PACKAGES));
    }
}

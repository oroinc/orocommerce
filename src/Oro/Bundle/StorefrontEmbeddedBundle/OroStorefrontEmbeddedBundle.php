<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\Compiler\PdfTemplateAssetPackagesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Embeds storefront pages into a headless host as JSON fragments, opens storefront sessions from OAuth2 tokens
 * and serves the built theme files and absolute asset urls such a host needs.
 */
class OroStorefrontEmbeddedBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PdfTemplateAssetPackagesPass());
    }
}

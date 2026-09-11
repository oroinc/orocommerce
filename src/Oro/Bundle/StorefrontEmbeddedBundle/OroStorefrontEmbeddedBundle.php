<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle;

use Oro\Bundle\StorefrontEmbeddedBundle\DependencyInjection\Compiler\PdfTemplateAssetPackagesPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroStorefrontEmbeddedBundle extends Bundle
{
    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new PdfTemplateAssetPackagesPass());
    }
}

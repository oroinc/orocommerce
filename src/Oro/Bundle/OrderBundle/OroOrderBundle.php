<?php

namespace Oro\Bundle\OrderBundle;

use Oro\Bundle\OrderBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceWithHandlerCompilerPass;
use Oro\Component\DependencyInjection\Compiler\TaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroOrderBundle extends Bundle
{
    use TaggedServiceTrait;

    #[\Override]
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new PriorityNamedTaggedServiceWithHandlerCompilerPass(
            'oro_order.importexport.converter.additional_converter_registry',
            'oro_order.external_order_import.additional_converter',
            function (array $attributes, string $serviceId, string $tagName): array {
                return [
                    $serviceId,
                    $this->getRequiredAttribute($attributes, 'entity', $serviceId, $tagName)
                ];
            }
        ));
    }
}

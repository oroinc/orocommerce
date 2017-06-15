<?php

namespace Oro\Bundle\PromotionBundle\DependencyInjection\Compiler;

use Oro\Component\DependencyInjection\Compiler\TaggedServicesCompilerPassTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PromotionContextConverterCompilerPass implements CompilerPassInterface
{
    use TaggedServicesCompilerPassTrait;
    
    const REGISTRY = 'oro_promotion.promotion.context_data_converter_registry';
    const TAG = 'oro_promotion.promotion_context_converter';

    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container, self::REGISTRY, self::TAG, 'registerConverter');
    }
}

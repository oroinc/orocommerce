<?php

namespace Oro\Bundle\PromotionBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPromotionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
            'Oro\Bundle\PromotionBundle\Entity\Promotion' => [
                'label' => 'labels',
                'description' => 'descriptions'
            ]
        ]));
        $container->addCompilerPass(new PriorityNamedTaggedServiceCompilerPass(
            'oro_promotion.discount.strategy_registry',
            'oro_promotion.discount_strategy',
            'alias'
        ));
        $container->addCompilerPass(new PromotionProductsGridCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}

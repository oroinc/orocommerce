<?php

namespace Oro\Bundle\PromotionBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Oro\Component\DependencyInjection\Compiler\PriorityNamedTaggedServiceCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The PromotionBundle bundle class.
 */
class OroPromotionBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroPromotionExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DefaultFallbackExtensionPass([
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

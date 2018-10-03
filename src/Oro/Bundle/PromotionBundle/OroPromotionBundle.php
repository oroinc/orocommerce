<?php

namespace Oro\Bundle\PromotionBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\LayoutBlockOptionsCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionProductsGridCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Oro\Bundle\PromotionBundle\Entity\Promotion;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * OroPromotionBundle adds coupon and promotion features to the OroCommerce application
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
            Promotion::class => [
                'label' => 'labels',
                'description' => 'descriptions',
            ],
        ]));
        $container
            ->addCompilerPass(new PromotionCompilerPass())
            ->addCompilerPass(new PromotionProductsGridCompilerPass())
            ->addCompilerPass(new LayoutBlockOptionsCompilerPass())
            ->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}

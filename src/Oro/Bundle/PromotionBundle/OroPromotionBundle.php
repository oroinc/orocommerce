<?php

namespace Oro\Bundle\PromotionBundle;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\DiscountContextConverterCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionContextConverterCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;

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
        $container->addCompilerPass(new DiscountContextConverterCompilerPass());
        $container->addCompilerPass(new PromotionContextConverterCompilerPass());
    }
}

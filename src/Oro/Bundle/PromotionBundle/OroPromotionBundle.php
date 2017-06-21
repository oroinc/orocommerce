<?php

namespace Oro\Bundle\PromotionBundle;

use Oro\Bundle\PromotionBundle\DependencyInjection\Compiler\PromotionCompilerPass;
use Oro\Bundle\PromotionBundle\DependencyInjection\OroPromotionExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
        $container->addCompilerPass(new PromotionCompilerPass());
    }
}

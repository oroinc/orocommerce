<?php

namespace Oro\Bundle\PromotionBundle;

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
}

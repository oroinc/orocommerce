<?php

namespace Oro\Bundle\OrderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;

class OroOrderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroOrderExtension();
        }

        return $this->extension;
    }
}

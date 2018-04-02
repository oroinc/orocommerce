<?php

namespace Oro\Bundle\OrderBundle;

use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

<?php

namespace Oro\Bundle\UPSBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;

class OroUPSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroUPSExtension();
        }

        return $this->extension;
    }
}

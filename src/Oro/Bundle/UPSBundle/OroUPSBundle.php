<?php

namespace Oro\Bundle\UPSBundle;

use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

<?php

namespace Oro\Bundle\ApruveBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ApruveBundle\DependencyInjection\OroApruveExtension;

class OroApruveBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroApruveExtension();
        }

        return $this->extension;
    }
}

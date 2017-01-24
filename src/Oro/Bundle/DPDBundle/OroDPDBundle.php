<?php

namespace Oro\Bundle\DPDBundle;

use Oro\Bundle\DPDBundle\DependencyInjection\OroDPDExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroDPDBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroDPDExtension();
        }

        return $this->extension;
    }
}

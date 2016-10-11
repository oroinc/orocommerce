<?php

namespace Oro\Bundle\WebCatalogBundle;

use Oro\Bundle\WebCatalogBundle\DependencyInjection\OroWebCatalogExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroWebCatalogBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroWebCatalogExtension();
        }

        return $this->extension;
    }
}

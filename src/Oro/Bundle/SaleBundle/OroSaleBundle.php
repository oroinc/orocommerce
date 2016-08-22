<?php

namespace Oro\Bundle\SaleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;

class OroSaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroSaleExtension();
        }

        return $this->extension;
    }
}

<?php

namespace Oro\Bundle\SaleBundle;

use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

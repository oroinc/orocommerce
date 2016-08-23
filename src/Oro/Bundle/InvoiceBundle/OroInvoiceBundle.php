<?php

namespace Oro\Bundle\InvoiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\InvoiceBundle\DependencyInjection\OroInvoiceExtension;

class OroInvoiceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroInvoiceExtension();
        }

        return $this->extension;
    }
}

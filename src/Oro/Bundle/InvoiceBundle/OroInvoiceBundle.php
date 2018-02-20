<?php

namespace Oro\Bundle\InvoiceBundle;

use Oro\Bundle\InvoiceBundle\DependencyInjection\OroInvoiceExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

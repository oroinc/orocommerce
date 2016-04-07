<?php

namespace OroB2B\Bundle\InvoiceBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\InvoiceBundle\DependencyInjection\OroB2BInvoiceExtension;

class OroB2BInvoiceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BInvoiceExtension();
        }

        return $this->extension;
    }
}

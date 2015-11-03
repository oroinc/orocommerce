<?php

namespace OroB2B\Bundle\SaleBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\SaleBundle\DependencyInjection\OroB2BSaleExtension;

class OroB2BSaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BSaleExtension();
        }

        return $this->extension;
    }
}

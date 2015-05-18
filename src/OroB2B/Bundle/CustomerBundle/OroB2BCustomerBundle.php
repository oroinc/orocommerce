<?php

namespace OroB2B\Bundle\CustomerBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\CustomerBundle\DependencyInjection\OroB2BCustomerExtension;

class OroB2BCustomerBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BCustomerExtension();
        }

        return $this->extension;
    }
}

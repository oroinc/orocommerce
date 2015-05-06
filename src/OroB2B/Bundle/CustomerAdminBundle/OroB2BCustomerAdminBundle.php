<?php

namespace OroB2B\Bundle\CustomerAdminBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\CustomerAdminBundle\DependencyInjection\OroB2BCustomerAdminExtension;

class OroB2BCustomerAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BCustomerAdminExtension();
        }

        return $this->extension;
    }
}

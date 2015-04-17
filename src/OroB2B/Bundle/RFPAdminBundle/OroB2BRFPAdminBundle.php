<?php

namespace OroB2B\Bundle\RFPAdminBundle;

use OroB2B\Bundle\RFPAdminBundle\DependencyInjection\OroB2BRFPAdminExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BRFPAdminBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BRFPAdminExtension();
        }

        return $this->extension;
    }
}

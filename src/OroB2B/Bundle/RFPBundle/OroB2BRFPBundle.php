<?php

namespace OroB2B\Bundle\RFPBundle;

use OroB2B\Bundle\RFPBundle\DependencyInjection\OroB2BRFPExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BRFPBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BRFPExtension();
        }

        return $this->extension;
    }
}

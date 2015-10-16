<?php

namespace OroB2B\Bundle\FrontendBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\FrontendBundle\DependencyInjection\OroB2BFrontendExtension;

class OroB2BFrontendBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BFrontendExtension();
        }

        return $this->extension;
    }
}

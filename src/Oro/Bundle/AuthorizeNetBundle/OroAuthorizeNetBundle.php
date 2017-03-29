<?php

namespace Oro\Bundle\AuthorizeNetBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\AuthorizeNetBundle\DependencyInjection\OroAuthorizeNetExtension;

class OroAuthorizeNetBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroAuthorizeNetExtension();
        }

        return $this->extension;
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle;

use Oro\Bundle\VisibilityBundle\DependencyInjection\OroVisibilityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroVisibilityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroVisibilityExtension();
        }

        return $this->extension;
    }
}

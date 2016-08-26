<?php

namespace Oro\Bundle\MoneyOrderBundle;

use Oro\Bundle\MoneyOrderBundle\DependencyInjection\OroMoneyOrderExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroMoneyOrderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroMoneyOrderExtension();
        }

        return $this->extension;
    }
}

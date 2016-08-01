<?php

namespace OroB2B\Bundle\MoneyOrderBundle;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BMoneyOrderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BMoneyOrderExtension();
        }

        return $this->extension;
    }
}

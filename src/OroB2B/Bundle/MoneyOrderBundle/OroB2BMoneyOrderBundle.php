<?php

namespace OroB2B\Bundle\MoneyOrderBundle;

use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\Compiler\PaymentConfigurationPass;
use OroB2B\Bundle\MoneyOrderBundle\DependencyInjection\OroB2BMoneyOrderExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new PaymentConfigurationPass());
    }
}

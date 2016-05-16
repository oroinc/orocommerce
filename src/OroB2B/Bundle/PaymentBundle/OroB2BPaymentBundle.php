<?php

namespace OroB2B\Bundle\PaymentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;

class OroB2BPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BPaymentExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        parent::build($container);
    }

    public function boot()
    {
        if (!SecureArrayType::hasType(SecureArrayType::TYPE)) {
            SecureArrayType::addType(
                SecureArrayType::TYPE,
                'OroB2B\Bundle\PaymentBundle\DBAL\Types\SecureArrayType'
            );

            $mcrypt = $this->container->get('oro_security.encoder.mcrypt');

            /** @var SecureArrayType $secureArrayType */
            $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
            $secureArrayType->setMcrypt($mcrypt);
        }
    }
}

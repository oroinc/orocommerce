<?php

namespace Oro\Bundle\PaymentBundle;

use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProviderPass;

use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroPaymentExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new PaymentMethodProviderPass());
        $container->addCompilerPass(new PaymentMethodViewPass());
        parent::build($container);
    }

    public function boot()
    {
        if (!SecureArrayType::hasType(SecureArrayType::TYPE)) {
            SecureArrayType::addType(
                SecureArrayType::TYPE,
                'Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType'
            );

            $mcrypt = $this->container->get('oro_security.encoder.mcrypt');

            /** @var SecureArrayType $secureArrayType */
            $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
            $secureArrayType->setMcrypt($mcrypt);
        }
    }
}

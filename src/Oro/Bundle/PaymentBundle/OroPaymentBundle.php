<?php

namespace Oro\Bundle\PaymentBundle;

use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodProvidersPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewProvidersPass;
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
        $container->addCompilerPass(new PaymentMethodProvidersPass());
        $container->addCompilerPass(new PaymentMethodViewProvidersPass());
        $container->addCompilerPass(new CompositePaymentMethodProviderCompilerPass());
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

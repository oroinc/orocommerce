<?php

namespace Oro\Bundle\PaymentBundle;

use Oro\Bundle\PaymentBundle\DBAL\Types\SecureArrayType;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\CompositePaymentMethodProviderCompilerPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentGuestAccessUrlPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\PaymentMethodViewProvidersPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\SupportsEntityPaymentContextFactoriesPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\PaymentBundle\DependencyInjection\OroPaymentExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * This class is setting up bundle configuration
 */
class OroPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroPaymentExtension();
    }

    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        $container->addCompilerPass(new PaymentMethodViewProvidersPass());
        $container->addCompilerPass(new CompositePaymentMethodProviderCompilerPass());
        $container->addCompilerPass(new SupportsEntityPaymentContextFactoriesPass());
        $container->addCompilerPass(new PaymentGuestAccessUrlPass());
        parent::build($container);
    }

    /** {@inheritdoc} */
    public function boot()
    {
        if (!SecureArrayType::hasType(SecureArrayType::TYPE)) {
            SecureArrayType::addType(
                SecureArrayType::TYPE,
                SecureArrayType::class
            );

            $crypter = $this->container->get('oro_security.encoder.default');

            /** @var SecureArrayType $secureArrayType */
            $secureArrayType = SecureArrayType::getType(SecureArrayType::TYPE);
            $secureArrayType->setCrypter($crypter);
        }
    }
}

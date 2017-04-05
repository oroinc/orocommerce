<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Symfony\Component\DependencyInjection\ContainerInterface;

trait EnabledPaymentMethodIdentifierTrait
{
    /**
     * @param string $name
     *
     * @return object
     */
    abstract public function getReference($name);

    /**
     * @return Channel|object
     */
    private function getChannel()
    {
        return $this->getReference(LoadChannelData::PAYMENT_TERM_INTEGRATION_CHANNEL);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return string
     */
    public function getPaymentMethodIdentifier(ContainerInterface $container)
    {
        return $container->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($this->getChannel());
    }
}

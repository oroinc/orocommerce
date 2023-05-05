<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Psr\Container\ContainerInterface;

trait EnabledPaymentMethodIdentifierTrait
{
    private function getChannel(): Channel
    {
        return $this->getReference(LoadChannelData::PAYMENT_TERM_INTEGRATION_CHANNEL);
    }

    private function getPaymentMethodIdentifier(ContainerInterface $container): string
    {
        return $container->get('oro_payment_term.config.integration_method_identifier_generator')
            ->generateIdentifier($this->getChannel());
    }
}

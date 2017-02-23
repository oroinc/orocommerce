<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\Helper;

use Oro\Bundle\FlatRateShippingBundle\Tests\Functional\DataFixtures\LoadFlatRateIntegration;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

trait FlatRateIntegrationTrait
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    abstract public function getReference($name);

    /**
     * @return string
     */
    protected function getFlatRateIdentifier()
    {
        $channel = $this->getChannelReference();

        return sprintf('flat_rate_%s', $channel->getId());
    }

    /**
     * @return string
     */
    protected function getFlatRatePrimaryIdentifier()
    {
        return 'primary';
    }

    /**
     * @return Channel
     */
    protected function getChannelReference()
    {
        return $this->getReference(LoadFlatRateIntegration::REFERENCE_FLAT_RATE);
    }
}

<?php

namespace Oro\Bundle\PaymentBundle\Method\Config;

use Oro\Bundle\IntegrationBundle\Entity\Channel;

abstract class AbstractPaymentConfig
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @param Channel $channel
     */
    public function __construct(Channel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        return $this->channel->getTransport()->getSettingsBag()->get($key);
    }
}

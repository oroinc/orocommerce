<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Configuration\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Zend\Crypt\Hmac;

class ClientDataProvider implements ClientDataProviderInterface
{
    /** @var InfinitePayConfigInterface */
    protected $config;

    public function __construct(InfinitePayConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * @param string $orderId
     *
     * @return ClientData
     */
    public function getClientData($orderId)
    {
        $clientData = new ClientData();
        $clientData->setClientRef($this->config->getClientRef());
        $message = $this->config->getClientRef().$orderId;
        $clientData->setSecurityCd(
            $this->generateSecurityCode($message, $this->config->getUsername())
        );

        return $clientData;
    }

    /**
     * @param string $message
     * @param string $secret
     *
     * @return string
     */
    private function generateSecurityCode($message, $secret)
    {
        $hmac = Hmac::compute($secret, 'sha256', $message);

        return base64_encode($hmac);
    }
}

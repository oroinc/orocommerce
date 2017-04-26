<?php

namespace Oro\Bundle\InfinitePayBundle\Action\Provider;

use Oro\Bundle\InfinitePayBundle\Method\Config\InfinitePayConfigInterface;
use Oro\Bundle\InfinitePayBundle\Service\InfinitePay\ClientData;
use Zend\Crypt\Hmac;

class ClientDataProvider implements ClientDataProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getClientData($orderId, InfinitePayConfigInterface $config)
    {
        $clientData = new ClientData();
        $clientData->setClientRef($config->getClientRef());
        $message = $config->getClientRef().$orderId;
        $clientData->setSecurityCd(
            $this->generateSecurityCode($message, $config->getSecret())
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

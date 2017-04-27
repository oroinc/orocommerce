<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Merchant\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class BasicGetMerchantRequestFactory implements GetMerchantRequestFactoryInterface
{
    /**
     * @internal
     */
    const URL_PATTERN = '/merchants/%s';

    /**
     * {@inheritDoc}
     */
    public function createByMerchantId($merchantId)
    {
        return new ApruveRequest(ApruveRestClient::METHOD_GET, $this->getUri($merchantId));
    }

    /**
     * @param string $merchantId
     *
     * @return string
     */
    private function getUri($merchantId)
    {
        return sprintf(self::URL_PATTERN, $merchantId);
    }
}

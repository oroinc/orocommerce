<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Merchant\Factory;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class BasicGetMerchantRequestFactory implements GetMerchantRequestFactoryInterface
{
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
        return sprintf('/merchants/%s', $merchantId);
    }
}

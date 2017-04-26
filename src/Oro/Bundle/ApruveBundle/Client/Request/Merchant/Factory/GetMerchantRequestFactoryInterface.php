<?php

namespace Oro\Bundle\ApruveBundle\Client\Request\Merchant\Factory;

use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;

interface GetMerchantRequestFactoryInterface
{
    /**
     * @param string $merchantId
     *
     * @return ApruveRequestInterface
     */
    public function createByMerchantId($merchantId);
}

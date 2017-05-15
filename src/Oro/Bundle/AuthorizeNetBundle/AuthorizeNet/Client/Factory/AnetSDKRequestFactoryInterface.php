<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

interface AnetSDKRequestFactoryInterface
{
    /**
     * @param array $options
     * @return AnetAPI\CreateTransactionRequest
     */
    public function createRequest(array $options = []);

    /**
     * @param AnetAPI\CreateTransactionRequest $request
     * @return AnetController\base\IApiOperation
     */
    public function createController(AnetAPI\CreateTransactionRequest $request);
}

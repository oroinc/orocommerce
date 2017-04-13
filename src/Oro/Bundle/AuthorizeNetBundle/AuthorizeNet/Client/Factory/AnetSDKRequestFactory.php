<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

class AnetSDKRequestFactory
{
    /**
     * @return AnetAPI\CreateTransactionRequest
     */
    public function createRequest()
    {
        return new AnetAPI\CreateTransactionRequest();
    }

    /**
     * @param AnetAPI\CreateTransactionRequest $request
     * @return AnetController\CreateTransactionController
     */
    public function createController(AnetAPI\CreateTransactionRequest $request)
    {
        return new AnetController\CreateTransactionController($request);
    }
}

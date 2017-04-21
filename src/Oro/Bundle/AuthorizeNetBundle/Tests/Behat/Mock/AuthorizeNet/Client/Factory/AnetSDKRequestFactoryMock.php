<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactory;
use Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\AuthorizeNet\Client\Factory\Api\CreateTransactionControllerMock;

class AnetSDKRequestFactoryMock extends AnetSDKRequestFactory
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
        return new CreateTransactionControllerMock($request);
    }
}

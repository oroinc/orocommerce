<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactory;
use Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\AuthorizeNet\Client\Factory\Api\CreateTransactionControllerMock;

final class AnetSDKRequestFactoryMock extends AnetSDKRequestFactory
{
    /**
     * {@inheritdoc}
     */
    public function createController(AnetAPI\CreateTransactionRequest $request)
    {
        return new CreateTransactionControllerMock($request);
    }
}

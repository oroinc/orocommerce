<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1 as AnetAPI;

interface RequestConfiguratorInterface
{
    /**
     * @return int
     */
    public function getPriority();

    /**
     * @param array $options
     * @return bool
     */
    public function isApplicable(array $options);

    /**
     * @param AnetAPI\CreateTransactionRequest $request
     * @param array $options
     */
    public function handle(AnetAPI\CreateTransactionRequest $request, array &$options);
}

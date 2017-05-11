<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\RequestConfiguratorRegistry;

class AnetSDKRequestFactory implements AnetSDKRequestFactoryInterface
{
    /**
     * @var RequestConfiguratorRegistry
     */
    private $requestConfiguratorRegistry;

    /**
     * {@inheritdoc}
     */
    public function __construct(RequestConfiguratorRegistry $requestConfiguratorRegistry)
    {
        $this->requestConfiguratorRegistry = $requestConfiguratorRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function createRequest(array $options = [])
    {
        $request = new AnetAPI\CreateTransactionRequest();

        $configurators = $this->requestConfiguratorRegistry->getRequestConfigurators();

        foreach ($configurators as $configurator) {
            if ($configurator->isApplicable($options)) {
                $configurator->handle($request, $options);
            }
        }

        return $request;
    }

    /**
     * {@inheritdoc}
     */
    public function createController(AnetAPI\CreateTransactionRequest $request)
    {
        return new AnetController\CreateTransactionController($request);
    }
}

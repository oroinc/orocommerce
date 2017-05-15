<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1\CreateTransactionResponse;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactoryInterface;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;

class AuthorizeNetSDKClient implements ClientInterface
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var AnetSDKRequestFactoryInterface
     */
    protected $requestFactory;

    /**
     * @param Serializer $serializer
     * @param AnetSDKRequestFactoryInterface $requestFactory
     */
    public function __construct(Serializer $serializer, AnetSDKRequestFactoryInterface $requestFactory)
    {
        $this->serializer = $serializer;
        $this->requestFactory = $requestFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function send($hostAddress, array $options = [])
    {
        $request = $this->requestFactory->createRequest($options);
        $controller = $this->requestFactory->createController($request);

        $apiResponse = $controller->executeWithApiResponse($hostAddress);

        if (!$apiResponse instanceof CreateTransactionResponse) {
            throw new \LogicException(sprintf(
                'Authorize.Net SDK API returned wrong response type. Expected: "%s". Actual: "%s"',
                CreateTransactionResponse::class,
                get_class($apiResponse)
            ));
        }

        return new AuthorizeNetSDKResponse($this->serializer, $apiResponse);
    }
}

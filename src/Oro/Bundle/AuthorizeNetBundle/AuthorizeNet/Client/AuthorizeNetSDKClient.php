<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1 as AnetAPI;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\Factory\AnetSDKRequestFactory;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;

class AuthorizeNetSDKClient implements ClientInterface
{
    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @var AnetSDKRequestFactory
     */
    protected $factory;

    /**
     * @param Serializer $serializer
     * @param AnetSDKRequestFactory $factory
     */
    public function __construct(Serializer $serializer, AnetSDKRequestFactory $factory)
    {
        $this->serializer = $serializer;
        $this->factory = $factory;
    }

    /**
     * {@inheritdoc}
     */
    public function send(array $options)
    {
        $request = $this->factory->createRequest();
        $request->setMerchantAuthentication(
            (new AnetAPI\MerchantAuthenticationType)
                ->setName($options[Option\ApiLoginId::API_LOGIN_ID])
                ->setTransactionKey($options[Option\TransactionKey::TRANSACTION_KEY])
        );
        $request->setTransactionRequest($this->getTransactionRequest($options));

        $controller = $this->factory->createController($request);

        $apiResponse = $controller->executeWithApiResponse($options[Option\Environment::ENVIRONMENT]);
        if (!$apiResponse instanceof CreateTransactionResponse) {
            throw new \LogicException('Authoreze.Net SDK API returned wrong response type.
                Expected: net\authorize\api\contract\v1\CreateTransactionResponse. Actual: ' .
                get_class($apiResponse));
        }

        return new AuthorizeNetSDKResponse($this->serializer, $apiResponse);
    }

    /**
     * @param array $options
     * @return AnetAPI\TransactionRequestType
     */
    protected function getTransactionRequest(array $options)
    {
        $transactionType = $options[Option\Transaction::TRANSACTION_TYPE];

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType($transactionType)
            ->setAmount($options[Option\Amount::AMOUNT])
            ->setCurrencyCode($options[Option\Currency::CURRENCY]);

        if ($transactionType === Option\Transaction::CAPTURE) {
            $transactionRequest->setRefTransId($options[Option\OriginalTransaction::ORIGINAL_TRANSACTION]);
        } else {
            $transactionRequest->setPayment($this->getPayment($options));
        }

        return $transactionRequest;
    }

    /**
     * @param array $options
     * @return AnetAPI\PaymentType
     */
    protected function getPayment(array $options)
    {
        return (new AnetAPI\PaymentType)
            ->setOpaqueData(
                (new AnetAPI\OpaqueDataType)
                    ->setDataDescriptor($options[Option\DataDescriptor::DATA_DESCRIPTOR])
                    ->setDataValue($options[Option\DataValue::DATA_VALUE])
            );
    }
}

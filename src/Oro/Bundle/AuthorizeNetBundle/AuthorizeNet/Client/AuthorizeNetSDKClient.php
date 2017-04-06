<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client;

use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response\AuthorizeNetSDKResponse;

class AuthorizeNetSDKClient implements ClientInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(array $options)
    {
        $request = new AnetAPI\CreateTransactionRequest();
        $request->setMerchantAuthentication(
            (new AnetAPI\MerchantAuthenticationType)
                ->setName($options[Option\ApiLoginId::API_LOGIN_ID])
                ->setTransactionKey($options[Option\TransactionKey::TRANSACTION_KEY])
        );
        $request->setTransactionRequest($this->getTransactionRequest($options));

        $controller = new AnetController\CreateTransactionController($request);

        /**@var AnetAPI\CreateTransactionResponse $apiResponse*/
        $apiResponse = $controller
            ->executeWithApiResponse($options[Option\Environment::ENVIRONMENT]);

        return new AuthorizeNetSDKResponse($apiResponse);
    }

    /**
     * @param array $options
     * @return AnetAPI\TransactionRequestType
     */
    protected function getTransactionRequest(array $options)
    {
        $transactionType = $options[Option\Transaction::TRANSACTION_TYPE];

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest
            ->setTransactionType($transactionType)
            ->setCurrencyCode($options[Option\Currency::CURRENCY])
            ->setPayment($this->getPayment($options));

        if ($transactionType === Option\Transaction::CAPTURE) {
            $transactionRequest->setRefTransId($options[Option\OriginalTransaction::ORIGINAL_TRANSACTION]);
        } else {
            $transactionRequest->setAmount($options[Option\Amount::AMOUNT]);
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

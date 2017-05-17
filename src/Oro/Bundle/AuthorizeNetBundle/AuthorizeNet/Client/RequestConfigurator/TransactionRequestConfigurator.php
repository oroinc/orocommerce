<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1 as AnetAPI;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class TransactionRequestConfigurator implements RequestConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $options)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(AnetAPI\CreateTransactionRequest $request, array &$options)
    {
        $request->setTransactionRequest($this->getTransactionRequest($options));

        // Remove handled options to prevent handling in fallback configurator
        unset(
            $options[Option\DataDescriptor::DATA_DESCRIPTOR],
            $options[Option\DataValue::DATA_VALUE],
            $options[Option\Amount::AMOUNT],
            $options[Option\Transaction::TRANSACTION_TYPE],
            $options[Option\Currency::CURRENCY],
            $options[Option\OriginalTransaction::ORIGINAL_TRANSACTION],
            $options[Option\SolutionId::SOLUTION_ID]
        );
    }

    /**
     * @param array $options
     * @return AnetAPI\TransactionRequestType
     */
    protected function getTransactionRequest(array $options)
    {
        $transactionRequest = new AnetAPI\TransactionRequestType();

        if (array_key_exists(Option\Transaction::TRANSACTION_TYPE, $options)) {
            $transactionRequest->setTransactionType($options[Option\Transaction::TRANSACTION_TYPE]);
        }

        if (array_key_exists(Option\Amount::AMOUNT, $options)) {
            $transactionRequest->setAmount($options[Option\Amount::AMOUNT]);
        }

        if (array_key_exists(Option\Currency::CURRENCY, $options)) {
            $transactionRequest->setCurrencyCode($options[Option\Currency::CURRENCY]);
        }

        if (array_key_exists(Option\OriginalTransaction::ORIGINAL_TRANSACTION, $options)) {
            $transactionRequest->setRefTransId($options[Option\OriginalTransaction::ORIGINAL_TRANSACTION]);
        }

        if (array_key_exists(Option\DataDescriptor::DATA_DESCRIPTOR, $options)
            && array_key_exists(Option\DataValue::DATA_VALUE, $options)) {
            $transactionRequest->setPayment($this->getPaymentType($options));
        }

        if (array_key_exists(Option\SolutionId::SOLUTION_ID, $options)) {
            $transactionRequest->setSolution($this->getSolutionType($options));
        }

        return $transactionRequest;
    }

    /**
     * @param array $options
     * @return AnetAPI\PaymentType
     */
    protected function getPaymentType(array $options)
    {
        $opaqueDataType = new AnetAPI\OpaqueDataType();
        $opaqueDataType
            ->setDataDescriptor($options[Option\DataDescriptor::DATA_DESCRIPTOR])
            ->setDataValue($options[Option\DataValue::DATA_VALUE]);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaqueDataType);

        return $paymentType;
    }

    /**
     * @param array $options
     * @return AnetAPI\SolutionType
     */
    protected function getSolutionType(array $options)
    {
        $solutionType = new AnetAPI\SolutionType();
        $solutionType->setId($options[Option\SolutionId::SOLUTION_ID]);

        return $solutionType;
    }
}

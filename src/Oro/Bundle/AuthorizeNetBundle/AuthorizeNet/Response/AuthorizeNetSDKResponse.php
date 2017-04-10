<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response;

use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType\ErrorAType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;

class AuthorizeNetSDKResponse implements ResponseInterface
{
    /**
     * @var CreateTransactionResponse
     */
    protected $apiResponse;

    /**
     * @param CreateTransactionResponse $apiResponse
     */
    public function __construct(CreateTransactionResponse $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function isSuccessful()
    {
        $transactionResponse = $this->apiResponse->getTransactionResponse();
        return $transactionResponse && $transactionResponse->getResponseCode() === '1';
    }

    /**
     * {@inheritdoc}
     */
    public function getReference()
    {
        $transactionResponse = $this->apiResponse->getTransactionResponse();
        return $transactionResponse ? $transactionResponse->getTransId() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->isSuccessful() ?
            $this->getSuccessMessage() :
            $this->getErrorMessage();
    }

    /**
     * @return null|string
     */
    protected function getSuccessMessage()
    {
        $messages = [];
        foreach ($this->apiResponse->getMessages()->getMessage() as $message) {
            $messages[] = "({$message->getCode()}) {$message->getText()}";
        }
        $transactionResponse = $this->apiResponse->getTransactionResponse();
        if ($transactionResponse) {
            /** @var MessageAType[] $transactionMessages */
            $transactionMessages = $transactionResponse->getMessages();
            foreach ($transactionMessages as $message) {
                $messages[] = "({$message->getCode()}) {$message->getDescription()}";
            }
        }
        return empty($messages) ? null : implode('  ', $messages);
    }

    /**
     * @return null|string
     */
    protected function getErrorMessage()
    {
        $errorMessages = [];
        foreach ($this->apiResponse->getMessages()->getMessage() as $error) {
            $errorMessages[] = "({$error->getCode()}) {$error->getText()}";
        }
        $transactionResponse = $this->apiResponse->getTransactionResponse();
        if ($transactionResponse) {
            /** @var ErrorAType[] $transactionErrors */
            $transactionErrors = $transactionResponse->getErrors();
            foreach ($transactionErrors as $error) {
                $errorMessages[] = "({$error->getErrorCode()}) {$error->getErrorText()}";
            }
        }
        return empty($errorMessages) ? null : implode('  ', $errorMessages);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        $transactionResponse = $this->apiResponse->getTransactionResponse();
        // TODO: consider increase volume of returned information about api response
        $data = ['message' => $this->getMessage()];
        if ($transactionResponse) {
            $data[Option\OriginalTransaction::ORIGINAL_TRANSACTION] = $transactionResponse->getTransId();
            $data['result'] = $transactionResponse->getResponseCode();
            $data['authCode'] = $transactionResponse->getAuthCode();
        }
        return $data;
    }
}

<?php

namespace Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Response;

use JMS\Serializer\Serializer;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType\ErrorAType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType;

class AuthorizeNetSDKResponse implements ResponseInterface
{
    /**
     * @var CreateTransactionResponse
     */
    protected $apiResponse;

    /**
     * @var array|null
     */
    protected $apiResponseSerialized;

    /**
     * @var Serializer
     */
    protected $serializer;

    /**
     * @param Serializer $serializer
     * @param CreateTransactionResponse $apiResponse
     */
    public function __construct(Serializer $serializer, CreateTransactionResponse $apiResponse)
    {
        $this->serializer = $serializer;
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
            /** @var MessageAType[]|null $transactionMessages */
            $transactionMessages = $transactionResponse->getMessages();
            if ($transactionMessages) { // $transactionResponse->getMessages() can return null sometimes
                foreach ($transactionMessages as $message) {
                    $messages[] = "({$message->getCode()}) {$message->getDescription()}";
                }
            }
        }

        return empty($messages) ? null : implode(';  ', $messages);
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
            /** @var ErrorAType[]|null $transactionErrors */
            $transactionErrors = $transactionResponse->getErrors();
            if ($transactionErrors) { // $transactionResponse->getErrors() can return null sometimes
                foreach ($transactionErrors as $error) {
                    $errorMessages[] = "({$error->getErrorCode()}) {$error->getErrorText()}";
                }
            }
        }

        return empty($errorMessages) ? null : implode(';  ', $errorMessages);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        if ($this->apiResponseSerialized === null) {
            $this->apiResponseSerialized = $this->cleanup($this->serializer->toArray($this->apiResponse));
        }

        return $this->apiResponseSerialized;
    }

    /**
     * @param array $response
     * @return array
     */
    protected function cleanup(array $response)
    {
        foreach ($response as $key => $value) {
            if (is_array($value)) {
                $response[$key] = $this->cleanup($value);
            }
            if ($response[$key] === [] || $response[$key] === '') {
                unset($response[$key]);
            }
        }

        return $response;
    }
}

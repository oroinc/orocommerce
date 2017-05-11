<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Behat\Mock\AuthorizeNet\Client\Factory\Api;

use net\authorize\api\contract\v1\CreateTransactionRequest;
use net\authorize\api\contract\v1\CreateTransactionResponse;
use net\authorize\api\contract\v1\MessagesType;
use net\authorize\api\contract\v1\TransactionResponseType;
use net\authorize\api\contract\v1\TransactionResponseType\MessagesAType\MessageAType;
use net\authorize\api\controller\base\ApiOperationBase;

class CreateTransactionControllerMock extends ApiOperationBase
{
    /** @var CreateTransactionRequest */
    protected $request;

    /**
     * @param CreateTransactionRequest $request
     */
    public function __construct(CreateTransactionRequest $request)
    {
        $this->request = $request;
    }

    /**
     * @param null|string $endPoint
     * @return CreateTransactionResponse
     */
    public function executeWithApiResponse($endPoint = null)
    {
        $response = new CreateTransactionResponse();

        $payment = $this->request->getTransactionRequest()->getPayment();
        if ($payment && $payment->getOpaqueData()->getDataValue() === 'special_data_value_for_api_error_emulation') {
            $messages = new MessagesType();
            $messages->setResultCode('Error');
            $messages->addToMessage(
                (new MessagesType\MessageAType())
                    ->setCode('E00114')
                    ->setText('Invalid OTS Token.')
            );
            $response->setMessages($messages);

            $response->setTransactionResponse(new TransactionResponseType());
        } else {
            $messages = new MessagesType();
            $messages->setResultCode('Ok');
            $messages->addToMessage(
                (new MessagesType\MessageAType())
                    ->setCode('I00001')
                    ->setText('Successful.')
            );
            $response->setMessages($messages);

            $transactionResponse = new TransactionResponseType();
            $transactionResponse
                ->setResponseCode('1')
                ->setAuthCode('01E43S')
                ->setAvsResultCode('Y')
                ->setCvvResultCode('P')
                ->setCavvResultCode('2')
                ->setTransId('60022132422')
                ->setRefTransID('02886C4D3363CFE3E925548C84092F01')
                ->setTestRequest('0')
                ->setAccountNumber('XXXX0002')
                ->setAccountType('AmericanExpress')
                ->addToMessages(
                    (new MessageAType())
                        ->setCode('1')
                        ->setDescription('This transaction has been approved.')
                );
            $response->setTransactionResponse($transactionResponse);
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getApiResponse()
    {
        throw new \RuntimeException('This method must not be called in tests');
    }

    /**
     * {@inheritdoc}
     */
    public function execute($endPoint = \net\authorize\api\constants\ANetEnvironment::CUSTOM)
    {
        throw new \RuntimeException('This method must not be called in tests');
    }
}

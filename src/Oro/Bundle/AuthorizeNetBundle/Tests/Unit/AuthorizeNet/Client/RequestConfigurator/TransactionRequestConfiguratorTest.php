<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1 as AnetAPI;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\TransactionRequestConfigurator;

class TransactionRequestConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    const SOLUTION_ID = 'AAA000001';

    /**
     * @var TransactionRequestConfigurator
     */
    protected $transactionRequestConfigurator;

    protected function setUp()
    {
        $this->transactionRequestConfigurator = new TransactionRequestConfigurator();
    }

    protected function tearDown()
    {
        unset($this->transactionRequestConfigurator);
    }

    public function testGetPriority()
    {
        $this->assertEquals(0, $this->transactionRequestConfigurator->getPriority());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->transactionRequestConfigurator->isApplicable([]));
    }

    /**
     * @dataProvider handleProvider
     * @param array $options
     * @param AnetAPI\TransactionRequestType $transactionRequestType
     */
    public function testHandle(array $options, AnetAPI\TransactionRequestType $transactionRequestType)
    {
        /** @var AnetAPI\CreateTransactionRequest|\PHPUnit_Framework_MockObject_MockObject $request * */
        $request = new AnetAPI\CreateTransactionRequest();

        $customOptions = ['some_another_options' => 'value'];
        $options = array_merge($options, $customOptions);

        $this->transactionRequestConfigurator->handle($request, $options);

        // Configurator options removed, options that are not related to this configurator left
        $this->assertSame($customOptions, $options);
        $this->assertEquals($transactionRequestType, $request->getTransactionRequest());
    }

    /**
     * @return array
     */
    public function handleProvider()
    {
        $opaqueData = new AnetAPI\OpaqueDataType();
        $opaqueData
            ->setDataDescriptor('data_desc')
            ->setDataValue('data_value');

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaqueData);

        $solutionType = new AnetApi\SolutionType();
        $solutionType->setId(self::SOLUTION_ID);

        return [
            'opaque parameters only' => [
                'options' => [
                    Option\DataDescriptor::DATA_DESCRIPTOR => 'data_desc',
                    Option\DataValue::DATA_VALUE => 'data_value',
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setPayment($paymentType),
            ],
            'opaque parameters only(only desc)' => [
                'options' => [
                    Option\DataDescriptor::DATA_DESCRIPTOR => 'data_desc',
                ],
                'transactionRequestType' => new AnetAPI\TransactionRequestType(),
            ],
            'opaque parameters only(only valye)' => [
                'options' => [
                    Option\DataValue::DATA_VALUE => 'data_value',
                ],
                'transactionRequestType' => new AnetAPI\TransactionRequestType(),
            ],
            'amount only' => [
                'options' => [
                    Option\Amount::AMOUNT => 1.00,
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setAmount(1.00),
            ],
            'transaction type only' => [
                'options' => [
                    Option\Transaction::TRANSACTION_TYPE => 'transaction',
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setTransactionType('transaction'),
            ],
            'currency only' => [
                'options' => [
                    Option\Currency::CURRENCY => 'USD',
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setCurrencyCode('USD'),
            ],
            'original transaction only' => [
                'options' => [
                    Option\OriginalTransaction::ORIGINAL_TRANSACTION => 'ref',
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setRefTransId('ref'),
            ],
            'all parameters together' => [
                'options' => [
                    Option\DataDescriptor::DATA_DESCRIPTOR => 'data_desc',
                    Option\DataValue::DATA_VALUE => 'data_value',
                    Option\Amount::AMOUNT => 1.00,
                    Option\Transaction::TRANSACTION_TYPE => 'transaction',
                    Option\Currency::CURRENCY => 'USD',
                    Option\OriginalTransaction::ORIGINAL_TRANSACTION => 'ref',
                    Option\SolutionId::SOLUTION_ID => self::SOLUTION_ID,
                ],
                'transactionRequestType' => (new AnetAPI\TransactionRequestType())
                    ->setPayment($paymentType)
                    ->setAmount(1.00)
                    ->setTransactionType('transaction')
                    ->setCurrencyCode('USD')
                    ->setRefTransId('ref')
                    ->setSolution($solutionType),
            ],
        ];
    }
}

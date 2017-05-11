<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Client\RequestConfigurator;

use net\authorize\api\contract\v1\CreateTransactionRequest;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Client\RequestConfigurator\MerchantAuthenticationRequestConfigurator;

class MerchantAuthenticationRequestConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MerchantAuthenticationRequestConfigurator
     */
    protected $merchantAuthenticationRequestConfigurator;

    protected function setUp()
    {
        $this->merchantAuthenticationRequestConfigurator = new MerchantAuthenticationRequestConfigurator();
    }

    protected function terDown()
    {
        unset($this->merchantAuthenticationRequestConfigurator);
    }

    public function testGetPriority()
    {
        $this->assertEquals(0, $this->merchantAuthenticationRequestConfigurator->getPriority());
    }

    public function testIsApplicable()
    {
        $this->assertFalse($this->merchantAuthenticationRequestConfigurator->isApplicable([]));

        $options = [
            Option\ApiLoginId::API_LOGIN_ID => 'api_login_id',
            Option\TransactionKey::TRANSACTION_KEY => 'transactionKey',
        ];

        $this->assertTrue($this->merchantAuthenticationRequestConfigurator->isApplicable($options));
    }

    public function testHandle()
    {
        /** @var CreateTransactionRequest $request */
        $request = new CreateTransactionRequest();

        $anotherOptions = ['someOption' => 'someValue'];

        $configuratorOptions = [
            Option\ApiLoginId::API_LOGIN_ID => 'api_login_id',
            Option\TransactionKey::TRANSACTION_KEY => 'transactionKey',
        ];

        $options = array_merge($anotherOptions, $configuratorOptions);

        $this->merchantAuthenticationRequestConfigurator->handle($request, $options);

        // Configurator options removed, options that are not related to this configurator left
        $this->assertSame($anotherOptions, $options);

        $merchantAuthentication = $request->getMerchantAuthentication();
        $this->assertNotNull($merchantAuthentication);
        $this->assertEquals($configuratorOptions[Option\ApiLoginId::API_LOGIN_ID], $merchantAuthentication->getName());
        $this->assertEquals(
            $configuratorOptions[Option\TransactionKey::TRANSACTION_KEY],
            $merchantAuthentication->getTransactionKey()
        );
    }
}

<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\Config;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfig;
use Oro\Bundle\PaymentBundle\Tests\Unit\Method\Config\AbstractPaymentConfigTestCase;

class AuthorizeNetConfigTest extends AbstractPaymentConfigTestCase
{
    /**
     * @var AuthorizeNetConfigInterface
     */
    protected $config;

    /**
     * {@inheritdoc}
     */
    protected function getPaymentConfig()
    {
        $params = [
            AuthorizeNetConfig::PAYMENT_METHOD_IDENTIFIER_KEY => 'test_payment_method_identifier',
            AuthorizeNetConfig::ADMIN_LABEL_KEY => 'test admin label',
            AuthorizeNetConfig::LABEL_KEY => 'test label',
            AuthorizeNetConfig::SHORT_LABEL_KEY => 'test short label',
            AuthorizeNetConfig::ALLOWED_CREDIT_CARD_TYPES_KEY => ['Master Card', 'Visa'],
            AuthorizeNetConfig::TEST_MODE_KEY => true,
            AuthorizeNetConfig::PURCHASE_ACTION_KEY => 'authorize',
            AuthorizeNetConfig::CLIENT_KEY => 'client key',
            AuthorizeNetConfig::API_LOGIN_ID => 'api login id',
            AuthorizeNetConfig::TRANSACTION_KEY => 'trans key',
        ];

        return new AuthorizeNetConfig($params);
    }

    public function testIsTestMode()
    {
        $this->assertSame(true, $this->config->isTestMode());
    }

    public function testGetPurchaseAction()
    {
        $this->assertSame('authorize', $this->config->getPurchaseAction());
    }

    public function testGetAllowedCreditCards()
    {
        $this->assertSame(['Master Card', 'Visa'], $this->config->getAllowedCreditCards());
    }

    public function testGetApiLoginId()
    {
        $this->assertSame('api login id', $this->config->getApiLoginId());
    }

    public function testGetTransactionKey()
    {
        $this->assertSame('trans key', $this->config->getTransactionKey());
    }

    public function testGetClientKey()
    {
        $this->assertSame('client key', $this->config->getClientKey());
    }
}

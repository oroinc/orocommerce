<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AbstractRequest;

abstract class AbstractRequestTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_REQUEST_OPTIONS = [
        Option\Transaction::TRANSACTION_TYPE => Option\Transaction::CAPTURE,
        Option\ApiLoginId::API_LOGIN_ID => 'some_login_id',
        Option\TransactionKey::TRANSACTION_KEY => 'some_transaction_key',
        Option\DataDescriptor::DATA_DESCRIPTOR => 'some_data_descriptor',
        Option\DataValue::DATA_VALUE => 'some_data_value',
        Option\Environment::ENVIRONMENT => \net\authorize\api\constants\ANetEnvironment::SANDBOX,
    ];

    /**
     * @return AbstractRequest
     */
    abstract protected function getRequest();

    /**
     * @return array
     */
    abstract protected function getOptions();

    public function testConfigureOptions()
    {
        $resolver = new Option\OptionsResolver();
        $this->getRequest()->configureOptions($resolver);
        $options = array_merge(static::DEFAULT_REQUEST_OPTIONS, $this->getOptions());
        self::assertEquals($options, $resolver->resolve($options));
    }
}

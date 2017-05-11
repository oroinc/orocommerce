<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AbstractRequest;

abstract class AbstractRequestTest extends \PHPUnit_Framework_TestCase
{
    const DEFAULT_REQUEST_OPTIONS = [
        Option\ApiLoginId::API_LOGIN_ID => 'some_login_id',
        Option\TransactionKey::TRANSACTION_KEY => 'some_transaction_key',
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

        $request = $this->getRequest();
        $request->configureOptions($resolver);

        $transactionType = $request->getTransactionType();

        $options = array_merge(
            static::DEFAULT_REQUEST_OPTIONS,
            [Option\Transaction::TRANSACTION_TYPE => $transactionType],
            $this->getOptions()
        );

        self::assertEquals($options, $resolver->resolve($options));
    }
}

<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AuthorizeRequest;

class AuthorizeRequestTest extends AbstractRequestTest
{
    /**
     * @var AuthorizeRequest;
     */
    protected $request;

    protected function setUp()
    {
        $this->request = new AuthorizeRequest();
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [
            Option\Amount::AMOUNT => 10.00,
            Option\Currency::CURRENCY => Option\Currency::US_DOLLAR,
            Option\DataDescriptor::DATA_DESCRIPTOR => 'some_data_descriptor',
            Option\DataValue::DATA_VALUE => 'some_data_value',
        ];
    }

    public function testTransactionType()
    {
        $this->assertEquals('authOnlyTransaction', $this->request->getTransactionType());
    }
}

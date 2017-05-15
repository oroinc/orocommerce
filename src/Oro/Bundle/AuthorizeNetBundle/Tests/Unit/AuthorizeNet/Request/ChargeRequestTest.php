<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\ChargeRequest;

class ChargeRequestTest extends AbstractRequestTest
{
    /**
     * @var ChargeRequest
     */
    protected $request;

    protected function setUp()
    {
        $this->request = new ChargeRequest();
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

    /**
     * {@inheritdoc}
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function testTransactionType()
    {
        $this->assertEquals('authCaptureTransaction', $this->request->getTransactionType());
    }
}

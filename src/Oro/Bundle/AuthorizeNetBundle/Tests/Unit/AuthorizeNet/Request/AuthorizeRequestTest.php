<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AbstractRequest;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AuthorizeRequest;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Option\Currency;

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
     * @return AbstractRequest
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
            Option\Currency::CURRENCY => Currency::US_DOLLAR,
        ];
    }

    public function testTransactionType()
    {
        $this->assertEquals('authOnlyTransaction', $this->request->getTransactionType());
    }
}

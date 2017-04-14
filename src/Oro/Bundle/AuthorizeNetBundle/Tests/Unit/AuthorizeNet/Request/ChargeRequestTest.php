<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\AbstractRequest;
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
        return [];
    }

    /**
     * @return AbstractRequest
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

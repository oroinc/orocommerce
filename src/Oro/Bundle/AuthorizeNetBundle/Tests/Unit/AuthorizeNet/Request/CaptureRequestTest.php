<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\AuthorizeNet\Request;

use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Option;
use Oro\Bundle\AuthorizeNetBundle\AuthorizeNet\Request\CaptureRequest;

class CaptureRequestTest extends AbstractRequestTest
{
    /**
     * @var CaptureRequest
     */
    protected $request;

    protected function setUp()
    {
        $this->request = new CaptureRequest();
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions()
    {
        return [
            Option\Amount::AMOUNT => 10.00,
            Option\Currency::CURRENCY => Option\Currency::US_DOLLAR,
            Option\OriginalTransaction::ORIGINAL_TRANSACTION => 1
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
        $this->assertEquals('priorAuthCaptureTransaction', $this->request->getTransactionType());
    }
}

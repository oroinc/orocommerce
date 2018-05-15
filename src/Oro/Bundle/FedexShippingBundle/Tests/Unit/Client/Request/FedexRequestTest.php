<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\Request;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use PHPUnit\Framework\TestCase;

class FedexRequestTest extends TestCase
{
    public function testGetRequestData()
    {
        $data = ['1', '2'];

        static::assertSame($data, (new FedexRequest($data))->getRequestData());
    }
}

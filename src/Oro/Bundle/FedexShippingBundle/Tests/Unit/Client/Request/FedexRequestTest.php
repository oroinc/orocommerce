<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\Request;

use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use PHPUnit\Framework\TestCase;

class FedexRequestTest extends TestCase
{
    public function testGetRequestData(): void
    {
        $uri = 'test/uri';
        $data = ['1', '2'];
        $isCheckMode = true;

        $request = new FedexRequest($uri, $data, $isCheckMode);

        self::assertEquals($uri, $request->getUri());
        self::assertEquals($data, $request->getRequestData());
        self::assertEquals($isCheckMode, $request->isCheckMode());
    }
}

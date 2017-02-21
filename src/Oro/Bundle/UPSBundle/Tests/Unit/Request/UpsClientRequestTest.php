<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Request;

use Oro\Bundle\UPSBundle\Request\UpsClientRequest;

class UpsClientRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $url = 'test';
        $data = [1, 2, 3];

        $request = new UpsClientRequest($url, $data);

        static::assertSame($url, $request->getUrl());
        static::assertSame($data, $request->getRequestData());
    }
}

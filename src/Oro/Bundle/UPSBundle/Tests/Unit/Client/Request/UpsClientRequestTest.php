<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Client\Request;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;

class UpsClientRequestTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters()
    {
        $url = 'test';
        $data = [1, 2, 3];

        $request = new UpsClientRequest([
            UpsClientRequest::FIELD_URL => $url,
            UpsClientRequest::FIELD_REQUEST_DATA => $data,
        ]);

        static::assertSame($url, $request->getUrl());
        static::assertSame($data, $request->getRequestData());
    }
}

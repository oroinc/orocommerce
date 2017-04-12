<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Apruve\Request;

use Oro\Bundle\ApruveBundle\Apruve\Request\ApruveRequest;
use Oro\Bundle\ApruveBundle\Apruve\Request\ApruveRequestDataInterface;

class ApruveRequestTest extends \PHPUnit_Framework_TestCase
{
    const URL = 'http://example.com';
    const DATA = [
        'merchantId' => 'sampleId',
    ];

    /**
     * @var ApruveRequestDataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestData;

    /**
     * @var ApruveRequest
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->requestData = $this->createMock(ApruveRequestDataInterface::class);
        $this->requestData
            ->method('getData')
            ->willReturn(self::DATA);

        $this->request = new ApruveRequest(self::URL, $this->requestData);
    }

    public function testGetUrl()
    {
        static::assertSame(self::URL, $this->request->getUrl());
    }

    public function testGetData()
    {
        static::assertSame(self::DATA, $this->request->getData());
    }
}

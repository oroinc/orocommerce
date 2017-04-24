<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Request;

use Oro\Bundle\ApruveBundle\Apruve\Model\ApruveEntityInterface;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequest;

class ApruveRequestTest extends \PHPUnit_Framework_TestCase
{
    const METHOD = 'GET';
    const URI = '/sampleUri';
    const DATA = ['sampleData' => ['foo' => 'bar']];

    /**
     * @var ApruveEntityInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestData;

    /**
     * @var ApruveRequest
     */
    private $request;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->requestData = $this->createMock(ApruveEntityInterface::class);
        $this->request = new ApruveRequest(self::METHOD, self::URI, $this->requestData);
    }

    public function testGetMethod()
    {
        $actual = $this->request->getMethod();
        static::assertSame(self::METHOD, $actual);
    }

    public function testGetUri()
    {
        $actual = $this->request->getUri();
        static::assertSame(self::URI, $actual);
    }

    public function testGetData()
    {
        $this->requestData
            ->method('getData')
            ->willReturn(self::DATA);
        $actual = $this->request->getData();

        static::assertSame(self::DATA, $actual);
    }

    public function testGetDataIfNoDataProvided()
    {
        $request = new ApruveRequest(self::METHOD, self::URI);
        $this->requestData
            ->method('getData')
            ->willReturn(self::DATA);
        $actual = $request->getData();

        static::assertSame([], $actual);
    }
}

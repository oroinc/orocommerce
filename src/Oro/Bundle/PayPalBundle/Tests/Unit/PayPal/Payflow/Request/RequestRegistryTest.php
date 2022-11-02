<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry;

class RequestRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequestRegistry */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = new RequestRegistry();
    }

    public function testAddRequest()
    {
        $request = $this->createMock(RequestInterface::class);
        $request->expects($this->once())
            ->method('getTransactionType')
            ->willReturn('X');

        $this->registry->addRequest($request);

        $this->assertSame($request, $this->registry->getRequest('X'));
    }

    public function testGetInvalidRequest()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Request with type "X" is missing. Registered requests are ""');

        $this->registry->getRequest('X');
    }

    public function testGetRequest()
    {
        $expectedRequest = $this->createMock(RequestInterface::class);
        $expectedRequest->expects($this->once())
            ->method('getTransactionType')
            ->willReturn('A');
        $this->registry->addRequest($expectedRequest);

        $actualRequest = $this->registry->getRequest('A');
        $this->assertSame($expectedRequest, $actualRequest);
    }
}

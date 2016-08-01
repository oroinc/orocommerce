<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\PayPal\Payflow\Request;

use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestRegistry;

class RequestRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var RequestRegistry */
    protected $registry;

    protected function setUp()
    {
        $this->registry = new RequestRegistry();
    }

    public function testAddRequest()
    {
        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $request */
        $request = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface');
        $request->expects($this->once())->method('getTransactionType')->willReturn('X');

        $this->registry->addRequest($request);

        $this->assertSame($request, $this->registry->getRequest('X'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Request with type "X" is missing. Registered requests are ""
     */
    public function testGetInvalidRequest()
    {
        $this->registry->getRequest('X');
    }

    public function testGetRequest()
    {
        /** @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject $expectedRequest */
        $expectedRequest = $this->getMock('Oro\Bundle\PayPalBundle\PayPal\Payflow\Request\RequestInterface');
        $expectedRequest->expects($this->once())->method('getTransactionType')->willReturn('A');
        $this->registry->addRequest($expectedRequest);

        $actualRequest = $this->registry->getRequest('A');
        $this->assertSame($expectedRequest, $actualRequest);
    }
}

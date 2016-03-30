<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\PayPal\Payflow\Request;

use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestRegistry;

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
        $request = $this->getMock('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\RequestInterface');
        $request->expects($this->once())->method('getAction')->willReturn('X');

        $this->registry->addRequest($request);

        $this->assertSame($request, $this->registry->getRequest('X'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Request with "X" action is missing. Registered request are "A, S, D, V"
     */
    public function testGetInvalidRequest()
    {
        $this->registry->getRequest('X');
    }

    public function testGetRequest()
    {
        $request = $this->registry->getRequest('A');
        $this->assertInstanceOf('OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Request\AuthorizationRequest', $request);
    }
}

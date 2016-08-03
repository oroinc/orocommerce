<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\EventListener\Callback;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\PayPalBundle\EventListener\Callback\PayflowIPCheckListener;

use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;

class PayflowIPCheckListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array[]
     */
    public function returnAllowedIPs()
    {
        return [
            'PayPal\'s IP address 1 should be allowed' => ['173.0.81.1'],
            'PayPal\'s IP address 2 should be allowed' => ['173.0.81.33'],
            'PayPal\'s IP address 3 should be allowed' => ['173.0.81.65'],
            'PayPal\'s IP address 4 should be allowed' => ['66.211.170.66'],
        ];
    }

    /**
     * @return array[]
     */
    public function returnNotAllowedIPs()
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider returnAllowedIPs
     * @param string $remoteAddress
     */
    public function testOnNotifyAllowed($remoteAddress)
    {
        $masterRequest = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $masterRequest->method('getClientIp')->will($this->returnValue($remoteAddress));

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->method('getMasterRequest')->will($this->returnValue($masterRequest));

        /** @var CallbackNotifyEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent');
        $event
            ->expects($this->never())
            ->method('markFailed');

        $listener = new PayflowIPCheckListener($requestStack);
        $listener->onNotify($event);
    }

    /**
     * @dataProvider returnNotAllowedIPs
     * @param string $remoteAddress
     */
    public function testOnNotifyNotAllowed($remoteAddress)
    {
        $masterRequest = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $masterRequest->method('getClientIp')->will($this->returnValue($remoteAddress));

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->method('getMasterRequest')->will($this->returnValue($masterRequest));

        /** @var CallbackNotifyEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent');
        $event
            ->expects($this->once())
            ->method('markFailed');

        $listener = new PayflowIPCheckListener($requestStack);
        $listener->onNotify($event);
    }

    public function testOnNotifyDontAllowIfMasterRequestEmpty()
    {
        $masterRequest = null;

        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->method('getMasterRequest')->will($this->returnValue($masterRequest));

        /** @var CallbackNotifyEvent|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMock('OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent');
        $event
            ->expects($this->once())
            ->method('markFailed');

        $listener = new PayflowIPCheckListener($requestStack);
        $listener->onNotify($event);
    }
}

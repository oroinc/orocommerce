<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener\Callback;

use OroB2B\Bundle\PaymentBundle\Event\CallbackNotifyEvent;
use OroB2B\Bundle\PaymentBundle\EventListener\Callback\PayflowIPCheckListener;
use Symfony\Component\HttpFoundation\RequestStack;

class PayflowIPCheckListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array[]
     */
    public function returnAlowedIPs()
    {
        return [
            'Paypal\'s IP address 1 should be allowed' => ['173.0.81.1'],
            'Paypal\'s IP address 2 should be allowed' => ['173.0.81.33'],
            'Paypal\'s IP address 3 should be allowed' => ['173.0.81.65'],
            'Paypal\'s IP address 4 should be allowed' => ['66.211.170.66'],
        ];
    }

    /**
     * @return array[]
     */
    public function returnNotAlowedIPs()
    {
        return [
            'Google\'s IP address 5 should not be allowed' => ['216.58.214.206'],
            'Facebook\'s IP address 6 should not be allowed' => ['173.252.120.68'],
        ];
    }

    /**
     * @dataProvider returnAlowedIPs
     * @param string $remoteAddr
     */
    public function testOnNotifyAllowed($remoteAddr)
    {
        $masterRequest = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $masterRequest->method('getClientIp')->will($this->returnValue($remoteAddr));

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
     * @dataProvider returnNotAlowedIPs
     * @param string $remoteAddr
     */
    public function testOnNotifyNotAllowed($remoteAddr)
    {
        $masterRequest = $this->getMock('Symfony\Component\HttpFoundation\Request');
        $masterRequest->method('getClientIp')->will($this->returnValue($remoteAddr));

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

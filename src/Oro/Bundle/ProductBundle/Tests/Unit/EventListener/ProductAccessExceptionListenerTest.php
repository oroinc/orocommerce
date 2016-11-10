<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\ProductBundle\EventListener\ProductAccessExceptionListener;

class ProductAccessExceptionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GetResponseForExceptionEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    /**
     * @var ProductAccessExceptionListener
     */
    private $testable;

    public function setUp()
    {
        $this->event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()->getMock();

        $this->requestStack = $this->getMock(RequestStack::class);

        $this->testable = new ProductAccessExceptionListener($this->requestStack);
    }

    public function testOtherException()
    {
        $exampleException = new NotFoundHttpException();
        $this->event->expects($this->once())
            ->method('getException')
            ->willReturn($exampleException);

        $this->testable->onAccessException($this->event);
    }

    public function testMyException()
    {
        $message = 'somemessage';

        $exampleException = new AccessDeniedHttpException($message);

        $this->event->expects($this->once())
            ->method('getException')
            ->willReturn($exampleException);

        $this->event->expects($this->once())
            ->method('setException')
            ->with(new NotFoundHttpException($message))
            ->willReturn($exampleException);

        $request = new Request();
        $request->attributes->set('_route', ProductAccessExceptionListener::PRODUCT_VIEW_ROUTE);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->testable->onAccessException($this->event);
    }
}

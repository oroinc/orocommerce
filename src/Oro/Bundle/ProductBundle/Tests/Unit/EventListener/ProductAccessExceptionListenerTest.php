<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductAccessExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ProductAccessExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ExceptionEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var ProductAccessExceptionListener
     */
    private $testable;

    protected function setUp(): void
    {
        $this->event = $this->createMock(ExceptionEvent::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->testable = new ProductAccessExceptionListener($this->requestStack);
    }

    public function testUnsupportedException()
    {
        $exampleException = new NotFoundHttpException();

        $this->event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($exampleException);

        $this->event->expects($this->never())
            ->method('setThrowable');

        $this->testable->onAccessException($this->event);
    }

    public function testUnsupportedRoute()
    {
        $request = new Request();
        $request->attributes->set('_route', 'unknown_route');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $exampleException = new AccessDeniedHttpException();

        $this->event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($exampleException);

        $this->testable->onAccessException($this->event);
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testAccessDeniedException(\Exception $exception)
    {
        $this->event->expects($this->once())
            ->method('getThrowable')
            ->willReturn($exception);

        $this->event->expects($this->once())
            ->method('setThrowable')
            ->with(new NotFoundHttpException('somemessage'));

        $request = new Request();
        $request->attributes->set('_route', ProductAccessExceptionListener::PRODUCT_VIEW_ROUTE);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->testable->onAccessException($this->event);
    }

    public function exceptionDataProvider(): array
    {
        return [
            [new AccessDeniedException('somemessage')],
            [new AccessDeniedHttpException('somemessage')],
            [new InsufficientAuthenticationException('somemessage')]
        ];
    }
}

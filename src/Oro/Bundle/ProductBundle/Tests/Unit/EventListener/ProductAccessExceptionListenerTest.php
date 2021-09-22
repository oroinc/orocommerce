<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductAccessExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ProductAccessExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private ProductAccessExceptionListener $testable;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->testable = new ProductAccessExceptionListener($this->requestStack);
    }

    public function testUnsupportedException(): void
    {
        $exampleException = new NotFoundHttpException();

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exampleException
        );

        $this->testable->onAccessException($event);

        self::assertSame($exampleException, $event->getThrowable());
    }

    public function testUnsupportedRoute(): void
    {
        $request = new Request();
        $request->attributes->set('_route', 'unknown_route');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $exampleException = new AccessDeniedHttpException();

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exampleException
        );

        $this->testable->onAccessException($event);

        self::assertSame($exampleException, $event->getThrowable());
    }

    /**
     * @dataProvider exceptionDataProvider
     */
    public function testAccessDeniedException(\Exception $exception): void
    {
        $request = new Request();
        $request->attributes->set('_route', ProductAccessExceptionListener::PRODUCT_VIEW_ROUTE);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->testable->onAccessException($event);

        self::assertEquals(new NotFoundHttpException('somemessage'), $event->getThrowable());
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

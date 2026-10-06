<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\EmbeddedFragmentCsrfProtectionListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\ActionControllerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmbeddedFragmentCsrfProtectionListenerTest extends TestCase
{
    private CsrfRequestManager&MockObject $csrfRequestManager;

    private EmbeddedFragmentCsrfProtectionListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->csrfRequestManager = $this->createMock(CsrfRequestManager::class);

        $this->listener = new EmbeddedFragmentCsrfProtectionListener($this->csrfRequestManager);
    }

    public function testDeniesTheFragmentSubRequestWhenTheCsrfTokenIsInvalid(): void
    {
        $request = Request::create('/', Request::METHOD_GET);
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_csrf_protection' => new CsrfProtection(),
        ]);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            [new ActionControllerStub(), 'saveAction'],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->csrfRequestManager
            ->expects(self::once())
            ->method('isRequestTokenValid')
            ->with(self::identicalTo($event->getRequest()), false)
            ->willReturn(false);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $this->listener->onKernelController($event);
    }

    public function testPassesTheFragmentSubRequestWithAValidTokenWithoutRefreshingIt(): void
    {
        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = Request::create('/', Request::METHOD_POST);
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_csrf_protection' => new CsrfProtection(),
        ]);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->csrfRequestManager
            ->expects(self::once())
            ->method('isRequestTokenValid')
            ->with(self::identicalTo($event->getRequest()), false)
            ->willReturn(true);
        $this->csrfRequestManager
            ->expects(self::never())
            ->method('refreshRequestToken');

        $this->listener->onKernelController($event);

        self::assertSame($controller, $event->getController());
    }

    public function testValidatesTheTokenFromTheRequestWhenTheAttributeAllowsIt(): void
    {
        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = Request::create('/', Request::METHOD_POST);
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_csrf_protection' => new CsrfProtection(true, true),
        ]);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );
        $this->csrfRequestManager
            ->expects(self::once())
            ->method('isRequestTokenValid')
            ->with(self::identicalTo($event->getRequest()), true)
            ->willReturn(true);

        $this->listener->onKernelController($event);

        self::assertSame($controller, $event->getController());
    }

    /**
     * @dataProvider skipsARequestThatNeedsNoCsrfCheckProvider
     *
     * @param array<string, mixed> $attributes
     */
    public function testSkipsARequestThatNeedsNoCsrfCheck(array $attributes, int $requestType): void
    {
        $this->csrfRequestManager
            ->expects(self::never())
            ->method('isRequestTokenValid');

        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = Request::create('/', Request::METHOD_POST);
        $request->attributes->add($attributes);
        $event = new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            $request,
            $requestType
        );

        $this->listener->onKernelController($event);

        self::assertSame($controller, $event->getController());
    }

    /**
     * @return iterable<string, array{array<string, mixed>, int}>
     */
    public static function skipsARequestThatNeedsNoCsrfCheckProvider(): iterable
    {
        yield 'main request' => [
            ['_csrf_protection' => new CsrfProtection()],
            HttpKernelInterface::MAIN_REQUEST,
        ];
        yield 'fragment sub-request without the attribute' => [
            [EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true],
            HttpKernelInterface::SUB_REQUEST,
        ];
        yield 'fragment sub-request with the attribute disabled' => [
            [
                EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
                '_csrf_protection' => new CsrfProtection(false),
            ],
            HttpKernelInterface::SUB_REQUEST,
        ];
    }
}

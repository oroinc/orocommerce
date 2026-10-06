<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\EmbeddedFragmentAccessListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Layout\Extension\EmbeddedContextConfigurator;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\ActionControllerStub;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\Stub\StaticActionControllerStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class EmbeddedFragmentAccessListenerTest extends TestCase
{
    private ClassAuthorizationChecker&MockObject $classAuthorizationChecker;

    private EmbeddedFragmentAccessListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->classAuthorizationChecker = $this->createMock(ClassAuthorizationChecker::class);

        $this->listener = new EmbeddedFragmentAccessListener($this->classAuthorizationChecker);
    }

    public function testDeniesTheFragmentSubRequestWhenTheControllerActionIsNotGranted(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(ActionControllerStub::class, 'saveAction')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            sprintf('Access denied to %s::saveAction.', ActionControllerStub::class)
        );

        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => false,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            [new ActionControllerStub(), 'saveAction'],
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);
    }

    public function testPassesTheFragmentSubRequestWhenTheControllerActionIsGranted(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(ActionControllerStub::class, 'saveAction')
            ->willReturn(true);

        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => false,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);

        self::assertSame($controller, $event->getController());
    }

    public function testSkipsTheControllerActionCheckWhenTheEntityCheckGrantedAccess(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::never())
            ->method('isClassMethodGranted');

        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => true,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);

        self::assertSame($controller, $event->getController());
    }

    public function testSkipsARequestWithoutTheEmbeddedFragmentMarker(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::never())
            ->method('isClassMethodGranted');

        $controller = [new ActionControllerStub(), 'saveAction'];
        $request = new Request();
        $request->attributes->add(['_oro_access_checked' => false]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            [],
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);

        self::assertSame($controller, $event->getController());
    }

    public function testChecksAClosureControllerAsClosureInvokeAsThePlatformDoes(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(\Closure::class, '__invoke')
            ->willReturn(true);

        $controller = static fn () => null;
        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => false,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);

        self::assertSame($controller, $event->getController());
    }

    public function testSkipsAStaticStringControllerAsThePlatformDoes(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::never())
            ->method('isClassMethodGranted');

        $controller = StaticActionControllerStub::class . '::staticAction';
        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => false,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);

        self::assertSame($controller, $event->getController());
    }

    public function testDeniesAStaticArrayControllerWhenItIsNotGranted(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(StaticActionControllerStub::class, 'staticAction')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            sprintf('Access denied to %s::staticAction.', StaticActionControllerStub::class)
        );

        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => false,
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            [StaticActionControllerStub::class, 'staticAction'],
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);
    }

    public function testChecksAgainWhenTheAccessCheckedFlagIsTruthyButNotTrue(): void
    {
        $this->classAuthorizationChecker
            ->expects(self::once())
            ->method('isClassMethodGranted')
            ->with(ActionControllerStub::class, 'saveAction')
            ->willReturn(false);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(
            sprintf('Access denied to %s::saveAction.', ActionControllerStub::class)
        );

        $request = new Request();
        $request->attributes->add([
            EmbeddedContextConfigurator::REQUEST_ATTRIBUTE => true,
            '_oro_access_checked' => '1',
        ]);
        $event = new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
            [new ActionControllerStub(), 'saveAction'],
            [],
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelControllerArguments($event);
    }
}

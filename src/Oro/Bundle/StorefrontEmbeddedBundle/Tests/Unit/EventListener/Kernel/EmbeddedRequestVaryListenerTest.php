<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener\Kernel;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\Kernel\EmbeddedRequestVaryListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class EmbeddedRequestVaryListenerTest extends TestCase
{
    private FrontendHelper&MockObject $frontendHelper;
    private EmbeddedRequestChecker&MockObject $embeddedRequestChecker;
    private EmbeddedRequestVaryListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->embeddedRequestChecker = $this->createMock(EmbeddedRequestChecker::class);
        $this->listener = new EmbeddedRequestVaryListener(
            $this->frontendHelper,
            $this->embeddedRequestChecker
        );
    }

    public function testVariesStorefrontResponseOnEmbeddedHeaderOnly(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(false);
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/customer/profile'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertSame([EmbeddedRequestChecker::EMBEDDED_HEADER], $response->getVary());
    }

    public function testVariesEmbeddedFragmentResponseOnEmbeddedAndHashNavigationHeaders(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/embedded/fragment/embedded_profile'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertSame(
            [
                EmbeddedRequestChecker::EMBEDDED_HEADER,
                ResponseHashnavListener::HASH_NAVIGATION_HEADER
            ],
            $response->getVary()
        );
    }

    public function testKeepsExistingVaryOfEmbeddedFragmentResponse(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->embeddedRequestChecker
            ->expects(self::once())
            ->method('isEmbeddedRequest')
            ->willReturn(true);
        $response = new Response();
        $response->setVary('Accept-Encoding');
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/embedded/fragment/embedded_profile'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertSame(
            [
                'Accept-Encoding',
                EmbeddedRequestChecker::EMBEDDED_HEADER,
                ResponseHashnavListener::HASH_NAVIGATION_HEADER
            ],
            $response->getVary()
        );
    }

    public function testDoesNothingForBackOfficeResponse(): void
    {
        $this->frontendHelper
            ->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/admin/customer/user/view/1'),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertSame([], $response->getVary());
    }

    public function testDoesNothingForSubRequestResponse(): void
    {
        $this->frontendHelper
            ->expects(self::never())
            ->method('isFrontendRequest');
        $this->embeddedRequestChecker
            ->expects(self::never())
            ->method('isEmbeddedRequest');
        $response = new Response();
        $event = new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/customer/profile'),
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        self::assertSame([], $response->getVary());
    }
}

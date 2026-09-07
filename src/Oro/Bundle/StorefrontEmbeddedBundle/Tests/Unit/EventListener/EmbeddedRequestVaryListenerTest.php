<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\StorefrontEmbeddedBundle\EventListener\EmbeddedRequestVaryListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EmbeddedRequestVaryListenerTest extends TestCase
{
    private FrontendHelper&MockObject $frontendHelper;
    private EmbeddedRequestVaryListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->listener = new EmbeddedRequestVaryListener($this->frontendHelper);
    }

    public function testTellsCachesThatAStorefrontPageDependsOnTheEmbeddedHeaders(): void
    {
        $this->frontendRequest(true);
        $response = new Response();

        $this->listener->onKernelResponse($this->responseEvent($response));

        self::assertSame(
            ['X-Oro-Embedded', 'X-Oro-Embedded-Proxy-Prefix', 'X-Oro-Embedded-Asset-Roots'],
            $response->getVary()
        );
    }

    public function testKeepsTheVaryTheResponseAlreadyCarries(): void
    {
        $this->frontendRequest(true);
        $response = new Response();
        $response->setVary('Accept-Encoding');

        $this->listener->onKernelResponse($this->responseEvent($response));

        self::assertSame(
            ['Accept-Encoding', 'X-Oro-Embedded', 'X-Oro-Embedded-Proxy-Prefix', 'X-Oro-Embedded-Asset-Roots'],
            $response->getVary()
        );
    }

    public function testSaysNothingAboutBackOfficeResponses(): void
    {
        $this->frontendRequest(false);
        $response = new Response();

        $this->listener->onKernelResponse($this->responseEvent($response));

        self::assertSame([], $response->getVary());
    }

    public function testSaysNothingAboutASubRequest(): void
    {
        $this->frontendHelper->expects(self::never())
            ->method('isFrontendRequest');
        $response = new Response();

        $this->listener->onKernelResponse($this->responseEvent($response, HttpKernelInterface::SUB_REQUEST));

        self::assertSame([], $response->getVary());
    }

    private function frontendRequest(bool $isFrontendRequest): void
    {
        $this->frontendHelper->expects(self::once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);
    }

    private function responseEvent(
        Response $response,
        int $requestType = HttpKernelInterface::MAIN_REQUEST
    ): ResponseEvent {
        return new ResponseEvent(
            $this->createMock(HttpKernelInterface::class),
            Request::create('/customer/profile'),
            $requestType,
            $response
        );
    }
}

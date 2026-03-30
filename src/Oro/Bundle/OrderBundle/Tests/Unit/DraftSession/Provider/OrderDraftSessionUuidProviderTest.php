<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\DraftSession\Provider;

use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderDraftSessionUuidProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RequestContextAwareInterface;

final class OrderDraftSessionUuidProviderTest extends TestCase
{
    private RequestContextAwareInterface&MockObject $router;
    private RequestContext&MockObject $requestContext;
    private OrderDraftSessionUuidProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->router = $this->createMock(RequestContextAwareInterface::class);
        $this->requestContext = $this->createMock(RequestContext::class);

        $this->provider = new OrderDraftSessionUuidProvider($this->router, 'orderDraftSessionUuid');
    }

    public function testGetDraftSessionUuidReturnsUuidFromRequestContext(): void
    {
        $expectedUuid = 'test-uuid-12345';

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('orderDraftSessionUuid')
            ->willReturn($expectedUuid);

        $result = $this->provider->getDraftSessionUuid();

        self::assertEquals($expectedUuid, $result);
    }

    public function testGetDraftSessionUuidReturnsNullWhenParameterIsNull(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('orderDraftSessionUuid')
            ->willReturn(null);

        $result = $this->provider->getDraftSessionUuid();

        self::assertNull($result);
    }

    public function testGetDraftSessionUuidReturnsEmptyString(): void
    {
        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('orderDraftSessionUuid')
            ->willReturn('');

        $result = $this->provider->getDraftSessionUuid();

        self::assertEquals('', $result);
    }

    public function testGetDraftSessionUuidWithCustomParameterName(): void
    {
        $provider = new OrderDraftSessionUuidProvider($this->router, 'customUuidParam');
        $expectedUuid = 'custom-uuid-789';

        $this->router
            ->expects(self::once())
            ->method('getContext')
            ->willReturn($this->requestContext);

        $this->requestContext
            ->expects(self::once())
            ->method('getParameter')
            ->with('customUuidParam')
            ->willReturn($expectedUuid);

        $result = $provider->getDraftSessionUuid();

        self::assertEquals($expectedUuid, $result);
    }
}

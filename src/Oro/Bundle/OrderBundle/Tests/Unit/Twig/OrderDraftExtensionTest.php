<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\DraftSession\Provider\OrderDraftSessionUuidProvider;
use Oro\Bundle\OrderBundle\Twig\OrderDraftExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class OrderDraftExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private OrderDraftSessionUuidProvider&MockObject $provider;
    private OrderDraftExtension $extension;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(OrderDraftSessionUuidProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_order.draft_session.provider.order_draft_session_uuid', $this->provider)
            ->getContainer($this);

        $this->extension = new OrderDraftExtension($container);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        self::assertCount(1, $functions);
        self::assertContainsOnly(TwigFunction::class, $functions);

        $function = $functions[0];
        self::assertInstanceOf(TwigFunction::class, $function);
        self::assertEquals('oro_order_draft_session_uuid', $function->getName());
        self::assertEquals([$this->extension, 'getDraftSessionUuid'], $function->getCallable());
    }

    public function testGetDraftSessionUuidReturnsUuid(): void
    {
        $expectedUuid = 'test-uuid-123';

        $this->provider->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($expectedUuid);

        $result = self::callTwigFunction($this->extension, 'oro_order_draft_session_uuid', []);

        self::assertEquals($expectedUuid, $result);
    }

    public function testGetDraftSessionUuidReturnsNull(): void
    {
        $this->provider->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $result = self::callTwigFunction($this->extension, 'oro_order_draft_session_uuid', []);

        self::assertNull($result);
    }

    public function testGetSubscribedServices(): void
    {
        $services = OrderDraftExtension::getSubscribedServices();

        self::assertIsArray($services);
        self::assertArrayHasKey('oro_order.draft_session.provider.order_draft_session_uuid', $services);
        self::assertEquals(
            OrderDraftSessionUuidProvider::class,
            $services['oro_order.draft_session.provider.order_draft_session_uuid']
        );
    }
}

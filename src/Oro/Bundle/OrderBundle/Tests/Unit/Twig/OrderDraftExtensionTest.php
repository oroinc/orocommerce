<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Twig;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Twig\OrderDraftExtension;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\Testing\ReflectionUtil;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Twig\TwigFunction;

final class OrderDraftExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private DraftSessionUuidProvider&MockObject $provider;

    private OrderDraftManager&MockObject $orderDraftManager;

    private OrderDraftExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = $this->createMock(DraftSessionUuidProvider::class);
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_order.draft_session.provider.draft_session_uuid', $this->provider)
            ->add('oro_order.draft_session.manager.order_draft', $this->orderDraftManager)
            ->getContainer($this);

        $this->extension = new OrderDraftExtension($container);
    }

    public function testGetFunctions(): void
    {
        $functions = $this->extension->getFunctions();

        self::assertCount(3, $functions);
        self::assertContainsOnly(TwigFunction::class, $functions);

        self::assertSame('oro_order_draft_session_uuid', $functions[0]->getName());
        self::assertSame([$this->extension, 'getDraftSessionUuid'], $functions[0]->getCallable());

        self::assertSame('oro_order_get_order_or_draft_id', $functions[1]->getName());
        self::assertSame([$this->extension, 'getOrderOrDraftId'], $functions[1]->getCallable());

        self::assertSame('oro_order_get_order_draft_id', $functions[2]->getName());
        self::assertSame([$this->extension, 'getOrderDraftId'], $functions[2]->getCallable());
    }

    public function testGetDraftSessionUuidReturnsUuid(): void
    {
        $expectedUuid = 'test-uuid-123';

        $this->provider->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn($expectedUuid);

        $result = self::callTwigFunction($this->extension, 'oro_order_draft_session_uuid', []);

        self::assertSame($expectedUuid, $result);
    }

    public function testGetDraftSessionUuidReturnsNull(): void
    {
        $this->provider->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $result = self::callTwigFunction($this->extension, 'oro_order_draft_session_uuid', []);

        self::assertNull($result);
    }

    public function testGetOrderOrDraftIdReturnsOrderIdWhenDraftSessionUuidIsNull(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 101);

        $this->provider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        self::assertSame(101, self::callTwigFunction($this->extension, 'oro_order_get_order_or_draft_id', [$order]));
    }

    public function testGetOrderOrDraftIdReturnsDraftIdWhenDraftSessionUuidIsPresent(): void
    {
        $order = new Order();
        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 404);
        $order->addDraft($orderDraft);

        $this->provider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('session-uuid');

        self::assertSame(404, self::callTwigFunction($this->extension, 'oro_order_get_order_or_draft_id', [$order]));
    }

    public function testGetOrderDraftIdReturnsNullWhenDraftSessionUuidIsNull(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 202);

        $this->provider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('findEntityDraft');

        self::assertNull(self::callTwigFunction($this->extension, 'oro_order_get_order_draft_id', [$order]));
    }

    public function testGetOrderDraftIdReturnsDraftIdWhenDraftSessionUuidIsPresent(): void
    {
        $order = new Order();
        ReflectionUtil::setId($order, 303);

        $orderDraft = new Order();
        ReflectionUtil::setId($orderDraft, 505);

        $this->provider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('session-uuid');

        $this->orderDraftManager
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($order)
            ->willReturn($orderDraft);

        self::assertSame(505, self::callTwigFunction($this->extension, 'oro_order_get_order_draft_id', [$order]));
    }

    public function testGetSubscribedServices(): void
    {
        $services = OrderDraftExtension::getSubscribedServices();

        self::assertIsArray($services);
        self::assertSame(
            [
                'oro_order.draft_session.provider.draft_session_uuid' => DraftSessionUuidProvider::class,
                'oro_order.draft_session.manager.order_draft' => OrderDraftManager::class,
            ],
            $services
        );
    }
}

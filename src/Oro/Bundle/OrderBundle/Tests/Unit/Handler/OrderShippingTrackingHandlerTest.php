<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Handler\OrderShippingTrackingHandler;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormInterface;

class OrderShippingTrackingHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var Order|\PHPUnit\Framework\MockObject\MockObject */
    private $order;

    /** @var OrderShippingTrackingHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->order = $this->createMock(Order::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->manager);

        $this->handler = new OrderShippingTrackingHandler($doctrine);
    }

    private function createShippingTracking(
        int $id,
        string $method,
        string $number,
        ?Order $order
    ): OrderShippingTracking {
        $orderShippingTracking = new OrderShippingTracking();
        ReflectionUtil::setId($orderShippingTracking, $id);
        $orderShippingTracking->setMethod($method);
        $orderShippingTracking->setNumber($number);
        $orderShippingTracking->setOrder($order);

        return $orderShippingTracking;
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcess(
        mixed $formData,
        ArrayCollection $existingEntities,
        int $persistedQty,
        int $removedQty
    ) {
        $this->form->expects(self::once())
            ->method('get')
            ->with('shippingTrackings')
            ->willReturnSelf();

        $this->form->expects(self::once())
            ->method('getData')
            ->willReturn($formData);

        $persistedEntities = [];
        $removedEntities = [];

        $this->order->expects(self::any())
            ->method('addShippingTracking')
            ->with(self::isInstanceOf(OrderShippingTracking::class))
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });

        $this->order->expects(self::any())
            ->method('removeShippingTracking')
            ->with(self::isInstanceOf(OrderShippingTracking::class))
            ->willReturnCallback(function ($entity) use (&$removedEntities) {
                $removedEntities[] = $entity;
            });

        $this->order->expects(self::any())
            ->method('getShippingTrackings')
            ->willReturn($existingEntities);

        $this->manager->expects($formData ? self::once() : self::never())
            ->method('flush');

        $this->handler->process($this->order, $this->form);

        self::assertCount($persistedQty, $persistedEntities);
        self::assertCount($removedQty, $removedEntities);
    }

    public function processDataProvider(): array
    {
        return [
            'no data' => [
                'formData' => null,
                'existingEntities' => new ArrayCollection([]),
                'persistedQty' => 0,
                'removedQty' => 0
            ],
            'empty data' => [
                'formData' => new ArrayCollection([]),
                'existingEntities' => new ArrayCollection([]),
                'persistedQty' => 0,
                'removedQty' => 0
            ],
            'persisted entities' => [
                'formData' => new ArrayCollection([
                    $this->createShippingTracking(1, 'UPS1', '1z111', $this->order),
                    $this->createShippingTracking(2, 'UPS2', '1z222', $this->order),
                    $this->createShippingTracking(3, 'UPS3', '1z333', $this->order),
                ]),
                'existingEntities' => new ArrayCollection([]),
                'persistedQty' => 3,
                'removedQty' => 0
            ],
            'removed and persisted entities' => [
                'formData' => new ArrayCollection([
                    $this->createShippingTracking(1, 'UPS1', '1z111', $this->order),
                    $this->createShippingTracking(2, 'UPS2', '1z222', $this->order),
                ]),
                'existingEntities' => new ArrayCollection([
                    $this->createShippingTracking(1, 'UPS1', '1z111', $this->order),
                    $this->createShippingTracking(2, 'UPS2', '1z222', $this->order),
                    $this->createShippingTracking(3, 'UPS3', '1z333', $this->order),
                ]),
                'persistedQty' => 2,
                'removedQty' => 1
            ]
        ];
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderShippingTracking;
use Oro\Bundle\OrderBundle\Form\Handler\OrderShippingTrackingHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class OrderShippingTrackingHandlerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager
     */
    protected $manager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Order
     */
    protected $order;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var OrderShippingTrackingHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');
        $this->manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $this->order = $this->getMock('Oro\Bundle\OrderBundle\Entity\Order');
        $this->request = new Request();

        $this->handler = new OrderShippingTrackingHandler(
            $this->form,
            $this->manager,
            $this->request,
            $this->order
        );
    }

    public function testProcessGet()
    {
        $this->form->expects(static::never())
            ->method('submit');

        $this->handler->process();
    }

    public function testProcessInvalidForm()
    {
        $this->request->setMethod('POST');

        $this->form->expects(static::once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects(static::once())
            ->method('isValid')
            ->willReturn(false);
        $this->form->expects(static::never())
            ->method('getData');

        $this->handler->process();
    }

    /**
     * @param mixed $formData
     * @param ArrayCollection $existingEntities
     * @param int $persistedQty
     * @param int $removedQty
     * @dataProvider processDataProvider
     */
    public function testProcess($formData, ArrayCollection $existingEntities, $persistedQty, $removedQty)
    {
        $this->request->setMethod('POST');

        $this->form->expects(static::once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects(static::once())
            ->method('isValid')
            ->willReturn(true);
        $this->form->expects(static::once())
            ->method('getData')
            ->willReturn($formData);

        $persistedEntities = [];
        $removedEntities = [];
        
        $this->order->expects(static::any())
            ->method('addShippingTracking')
            ->with(static::isInstanceOf('Oro\Bundle\OrderBundle\Entity\OrderShippingTracking'))
            ->willReturnCallback(
                function ($entity) use (&$persistedEntities) {
                    $persistedEntities[] = $entity;
                }
            );

        $this->order->expects(static::any())
            ->method('removeShippingTracking')
            ->with(static::isInstanceOf('Oro\Bundle\OrderBundle\Entity\OrderShippingTracking'))
            ->willReturnCallback(
                function ($entity) use (&$removedEntities) {
                    $removedEntities[] = $entity;
                }
            );

        $this->order->expects(static::any())
            ->method('getShippingTrackings')
            ->willReturn($existingEntities);

        $this->manager->expects($formData && count($formData) ? static::once() : static::never())
            ->method('flush');

        $this->handler->process();

        static::assertCount($persistedQty, $persistedEntities);
        static::assertCount($removedQty, $removedEntities);
    }

    /**
     * @return array
     */
    public function processDataProvider()
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

    /**
     * @param int $id
     * @param string $method
     * @param string $number
     * @param Order $order
     * @return OrderShippingTracking
     */
    protected function createShippingTracking($id, $method, $number, $order)
    {
        return $this->getEntity(
            'Oro\Bundle\OrderBundle\Entity\OrderShippingTracking',
            [
                'id' => $id,
                'method' => $method,
                'number' => $number,
                'order' => $order,
            ]
        );
    }
}

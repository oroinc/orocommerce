<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Api\Processor\Order\TotalsOrderApiProcessor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Oro\Component\ChainProcessor\ContextInterface;

class TotalsOrderApiProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TotalHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderTotalsHelperMock;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var TotalsOrderApiProcessor
     */
    private $testProcessor;

    public function setUp()
    {
        $this->orderTotalsHelperMock = $this->createMock(TotalHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->testProcessor = new TotalsOrderApiProcessor($this->orderTotalsHelperMock, $this->doctrineHelper);
    }

    /**
     * @return Order|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createOrderMock()
    {
        return $this->createMock(Order::class);
    }

    /**
     * @return EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @param Order $order
     */
    private function mockTotalHelperExpectenciesOnceCalled(Order $order)
    {
        $this->orderTotalsHelperMock
            ->expects(static::once())
            ->method('fillDiscounts')
            ->with($order);

        $this->orderTotalsHelperMock
            ->expects(static::once())
            ->method('fillSubtotals')
            ->with($order);

        $this->orderTotalsHelperMock
            ->expects(static::once())
            ->method('fillTotal')
            ->with($order);

        $entityManagerMock = $this->createEntityManagerMock();

        $entityManagerMock
            ->expects(static::once())
            ->method('persist')
            ->with($order);

        $entityManagerMock
            ->expects(static::once())
            ->method('flush');

        $this->doctrineHelper
            ->expects(static::once())
            ->method('getEntityManager')
            ->with($order)
            ->willReturn($entityManagerMock);
    }

    private function mockTotalHelperExpectenciesNeverCalled()
    {
        $this->orderTotalsHelperMock
            ->expects(static::never())
            ->method('fillDiscounts');

        $this->orderTotalsHelperMock
            ->expects(static::never())
            ->method('fillSubtotals');

        $this->orderTotalsHelperMock
            ->expects(static::never())
            ->method('fillTotal');

        $this->doctrineHelper
            ->expects(static::never())
            ->method('getEntityManager');
    }

    public function testProcess()
    {
        $orderMock = $this->createOrderMock();
        $contextMock = $this->createMock(FormContext::class);

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn(['someData' => 'someValue']);

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($orderMock);

        $this->mockTotalHelperExpectenciesOnceCalled($orderMock);

        $this->testProcessor->process($contextMock);
    }

    public function testWrongContext()
    {
        $contextMock = $this->createMock(ContextInterface::class);

        $this->mockTotalHelperExpectenciesNeverCalled();

        $this->testProcessor->process($contextMock);
    }

    public function testEmptyRequestData()
    {
        $orderMock = $this->createMock(Order::class);
        $contextMock = $this->createMock(FormContext::class);

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn([]);

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($orderMock);

        $this->mockTotalHelperExpectenciesNeverCalled();

        $this->testProcessor->process($contextMock);
    }

    public function testWrongResultInstance()
    {
        $contextMock = $this->createMock(FormContext::class);

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn([]);

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new \stdClass());

        $this->mockTotalHelperExpectenciesNeverCalled();

        $this->testProcessor->process($contextMock);
    }
}

<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\Api\Processor\Order;

use Oro\Bundle\OrderBundle\Api\Processor\Order\WebsiteOrderApiProcessor;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;

class WebsiteOrderApiProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var WebsiteOrderApiProcessor
     */
    protected $testedProcessor;

    public function setUp()
    {
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->testedProcessor = new WebsiteOrderApiProcessor($this->websiteManager);
    }

    /**
     * @return FormContext|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock()
    {
        return $this->createMock(FormContext::class);
    }

    /**
     * @return Order|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOrderMock()
    {
        return $this->createMock(Order::class);
    }

    /**
     * @return Website|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createWebsiteMock()
    {
        return $this->createMock(Website::class);
    }

    /**
     * @param ContextInterface|\PHPUnit_Framework_MockObject_MockObject $contextMock
     * @param Order|\PHPUnit_Framework_MockObject_MockObject            $orderMock
     */
    protected function mockExpectedFailedResult(ContextInterface $contextMock, Order $orderMock)
    {
        $contextMock
            ->expects(static::never())
            ->method('setResult');

        $orderMock
            ->expects(static::never())
            ->method('setWebsite');
    }

    /**
     * @param FormContext|\PHPUnit_Framework_MockObject_MockObject $contextMock
     * @param Order|\PHPUnit_Framework_MockObject_MockObject       $orderMock
     * @param Website|\PHPUnit_Framework_MockObject_MockObject     $websiteMock
     */
    protected function mockExpectedSuccessResult(FormContext $contextMock, Order $orderMock, Website $websiteMock)
    {
        $orderMock
            ->expects(static::once())
            ->method('setWebsite')
            ->with($websiteMock);

        $contextMock
            ->expects(static::once())
            ->method('setResult')
            ->with($orderMock);
    }

    public function testSuccessfulProcess()
    {
        $contextMock = $this->createContextMock();
        $orderMock = $this->createOrderMock();
        $websiteMock = $this->createWebsiteMock();
        $requestData = [];

        $this->websiteManager
            ->expects(static::exactly(2))
            ->method('getDefaultWebsite')
            ->willReturn($websiteMock);

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($orderMock);

        $contextMock
            ->expects(static::any())
            ->method('getRequestData')
            ->willReturn($requestData);

        $orderMock
            ->expects(static::once())
            ->method('getWebsite')
            ->willReturn(null);

        $this->mockExpectedSuccessResult($contextMock, $orderMock, $websiteMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongContext()
    {
        $contextMock = $this->createMock(ContextInterface::class);
        $orderMock = $this->createOrderMock();

        $this->mockExpectedFailedResult($contextMock, $orderMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testWrongResult()
    {
        $contextMock = $this->createContextMock();
        $orderMock = $this->createOrderMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new \stdClass());

        $this->mockExpectedFailedResult($contextMock, $orderMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testWebsiteExists()
    {
        $contextMock = $this->createContextMock();
        $orderMock = $this->createOrderMock();
        $requestData = ['website' => 'someWebsite'];

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($orderMock);

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->mockExpectedFailedResult($contextMock, $orderMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testNoDefaultWebsite()
    {
        $contextMock = $this->createContextMock();
        $orderMock = $this->createOrderMock();
        $requestData = [];

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($orderMock);

        $contextMock
            ->expects(static::once())
            ->method('getRequestData')
            ->willReturn($requestData);

        $this->websiteManager
            ->expects(static::once())
            ->method('getDefaultWebsite')
            ->willReturn(null);

        $this->mockExpectedFailedResult($contextMock, $orderMock);

        $this->testedProcessor->process($contextMock);
    }
}

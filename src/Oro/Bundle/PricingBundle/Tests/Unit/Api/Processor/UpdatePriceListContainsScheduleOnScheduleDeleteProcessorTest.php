<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use PHPUnit\Framework\TestCase;

class UpdatePriceListContainsScheduleOnScheduleDeleteProcessorTest extends TestCase
{
    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteHandler;

    /**
     * @var UpdatePriceListContainsScheduleOnScheduleDeleteProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new UpdatePriceListContainsScheduleOnScheduleDeleteProcessor(
            $this->doctrineHelper,
            $this->deleteHandler
        );
    }

    public function testProcessNoData()
    {
        $this->doctrineHelper->expects(static::any())
            ->method('getEntityManager');

        $this->deleteHandler->expects(static::any())
            ->method('process');

        $this->processor->process($this->createMock(ContextInterface::class));
    }

    public function testProcess()
    {
        $priceList = new PriceList();
        $priceList->setContainSchedule(true);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn((new PriceListSchedule())->setPriceList($priceList));

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->createMock(EntityManager::class));

        $this->processor->process($context);

        static::assertFalse($priceList->isContainSchedule());
    }
}

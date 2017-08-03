<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Processor;

//@codingStandardsIgnoreStart
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Processor\UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use PHPUnit\Framework\TestCase;
//@codingStandardsIgnoreEnd

class UpdatePriceListContainsScheduleOnScheduleDeleteListProcessorTest extends TestCase
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
     * @var UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->deleteHandler = $this->createMock(ProcessorInterface::class);

        $this->processor = new UpdatePriceListContainsScheduleOnScheduleDeleteListProcessor(
            $this->doctrineHelper,
            $this->deleteHandler
        );
    }

    public function testProcessNotArray()
    {
        $this->doctrineHelper->expects(static::never())
            ->method('getEntityManager');

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->processor->process($this->createMock(ContextInterface::class));
    }

    public function testProcess()
    {
        $priceList = new PriceList();
        $priceList->setContainSchedule(true);

        $priceListWithSchedule = new PriceList();
        $priceListWithSchedule->addSchedule(new PriceListSchedule());
        $priceListWithSchedule->setContainSchedule(false);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn([
                (new PriceListSchedule())->setPriceList($priceList),
                (new PriceListSchedule())->setPriceList($priceListWithSchedule),
                new \StdClass(),
                new PriceListSchedule(),
            ]);

        $this->deleteHandler->expects(static::once())
            ->method('process');

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityManager')
            ->willReturn($this->createMock(EntityManager::class));

        $this->processor->process($context);

        static::assertTrue($priceListWithSchedule->isContainSchedule());
        static::assertFalse($priceList->isContainSchedule());
    }
}

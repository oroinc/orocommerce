<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceListSchedule\Form;

use Oro\Bundle\PricingBundle\Api\PriceListSchedule\Form\AddSchedulesToPriceListApiFormSubscriber;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class AddSchedulesToPriceListApiFormSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AddSchedulesToPriceListApiFormSubscriber
     */
    private $testedSubscriber;

    protected function setUp()
    {
        $this->testedSubscriber = new AddSchedulesToPriceListApiFormSubscriber();
    }

    /**
     * @param $formMock
     * @param $priceListScheduleMock
     * @param $eventMock
     */
    protected function prepareMocksForSubmit($formMock, $priceListScheduleMock, $eventMock)
    {
        $formMock
            ->expects(static::once())
            ->method('has')
            ->with('priceList')
            ->willReturn(true);

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn($priceListScheduleMock);

        $eventMock
            ->expects(static::once())
            ->method('getForm')
            ->willReturn($formMock);
    }

    public function testGetSubscriberEvents()
    {
        $this->assertEquals(
            [
                FormEvents::SUBMIT => 'onSubmit'
            ],
            AddSchedulesToPriceListApiFormSubscriber::getSubscribedEvents()
        );
    }

    public function testOnSubmitSuccess()
    {
        $formMock = $this->createMock(FormInterface::class);
        $priceListScheduleMock = $this->createMock(PriceListSchedule::class);
        $priceListMock = $this->createMock(PriceList::class);
        $eventMock = $this->createMock(FormEvent::class);

        $this->prepareMocksForSubmit($formMock, $priceListScheduleMock, $eventMock);

        $priceListScheduleMock
            ->expects(static::once())
            ->method('getPriceList')
            ->willReturn($priceListMock);

        $priceListMock
            ->expects(static::once())
            ->method('addSchedule')
            ->with($priceListScheduleMock);

        $this->testedSubscriber->onSubmit($eventMock);
    }

    public function testOnSubmitNoPriceList()
    {
        $formMock = $this->createMock(FormInterface::class);
        $priceListScheduleMock = $this->createMock(PriceListSchedule::class);
        $priceListMock = $this->createMock(PriceList::class);
        $eventMock = $this->createMock(FormEvent::class);

        $this->prepareMocksForSubmit($formMock, $priceListScheduleMock, $eventMock);

        $priceListScheduleMock
            ->expects(static::once())
            ->method('getPriceList')
            ->willReturn(null);

        $priceListMock
            ->expects(static::never())
            ->method('addSchedule');

        $this->testedSubscriber->onSubmit($eventMock);
    }

    public function testOnSubmitNoPriceListForm()
    {
        $formMock = $this->createMock(FormInterface::class);
        $priceListScheduleMock = $this->createMock(PriceListSchedule::class);
        $priceListMock = $this->createMock(PriceList::class);
        $eventMock = $this->createMock(FormEvent::class);

        $formMock
            ->expects(static::once())
            ->method('has')
            ->with('priceList')
            ->willReturn(false);

        $eventMock
            ->expects(static::never())
            ->method('getData');

        $eventMock
            ->expects(static::once())
            ->method('getForm')
            ->willReturn($formMock);

        $priceListScheduleMock
            ->expects(static::never())
            ->method('getPriceList');

        $priceListMock
            ->expects(static::never())
            ->method('addSchedule');

        $this->testedSubscriber->onSubmit($eventMock);
    }

    public function testOnSubmitNotScheduleForm()
    {
        $formMock = $this->createMock(FormInterface::class);
        $priceListScheduleMock = $this->createMock(PriceListSchedule::class);
        $priceListMock = $this->createMock(PriceList::class);
        $eventMock = $this->createMock(FormEvent::class);

        $formMock
            ->expects(static::once())
            ->method('has')
            ->with('priceList')
            ->willReturn(true);

        $eventMock
            ->expects(static::once())
            ->method('getData')
            ->willReturn(null);

        $eventMock
            ->expects(static::once())
            ->method('getForm')
            ->willReturn($formMock);

        $priceListScheduleMock
            ->expects(static::never())
            ->method('getPriceList');

        $priceListMock
            ->expects(static::never())
            ->method('addSchedule');

        $this->testedSubscriber->onSubmit($eventMock);
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\Test\FormInterface;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\EventListener\PriceListListener;
use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;

class PriceListListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var PriceList
     */
    protected $priceList;

    /**
     * @var PriceListListener
     */
    protected $listener;

    /**
     * @var CombinedPriceListActivationPlanBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $builder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builder = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PriceListListener($this->builder);
        $this->priceList = $this->createPriceList();
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));
    }

    public function testWhenScheduleCollectionWasNotChanged()
    {
        $this->builder->expects($this->never())
            ->method('buildByPriceList');

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testWhenScheduleCollectionWasChanged()
    {
        /** @var PriceListSchedule $schedule */
        $schedule = $this->priceList->getSchedules()->first();
        $schedule->setActiveAt(new \DateTime('2016-01-01T22:00:00Z'));

        $this->builder->expects($this->once())
            ->method('buildByPriceList')
            ->with($this->priceList);

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testWhenScheduleCollectionElementDeleted()
    {
        $this->priceList->getSchedules()->remove(1);

        $this->builder->expects($this->once())
            ->method('buildByPriceList')
            ->with($this->priceList);

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    /**
     * @return PriceList
     */
    protected function createPriceList()
    {
        /** @var PriceList $priceList */
        $priceList = $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);
        $schedule1 = new PriceListSchedule(
            new \DateTime('2016-03-01T22:00:00Z'),
            new \DateTime('2016-04-01T22:00:00Z')
        );
        $schedule2 = new PriceListSchedule(
            new \DateTime('2016-05-01T22:00:00Z'),
            new \DateTime('2016-06-01T22:00:00Z')
        );

        $priceList->addSchedule($schedule1)
            ->addSchedule($schedule2);

        return $priceList;
    }

    /**
     * @param PriceList $priceList
     * @return FormProcessEvent
     */
    protected function createFormProcessEvent(PriceList $priceList)
    {
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        return new FormProcessEvent($form, $priceList);
    }

    /**
     * @param PriceList $priceList
     * @return AfterFormProcessEvent
     */
    protected function createAfterFormProcessEvent(PriceList $priceList)
    {
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        return new AfterFormProcessEvent($form, $priceList);
    }
}

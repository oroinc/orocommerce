<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\PricingBundle\Builder\CombinedPriceListActivationPlanBuilder;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Oro\Bundle\PricingBundle\EventListener\PriceListListener;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\Test\FormInterface;

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
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerHandler;

    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleLexemeHandler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->builder = $this
            ->getMockBuilder(CombinedPriceListActivationPlanBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->triggerHandler = $this
            ->getMockBuilder(PriceListRelationTriggerHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceRuleLexemeHandler = $this
            ->getMockBuilder(PriceRuleLexemeHandler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new PriceListListener(
            $this->builder,
            $this->triggerHandler,
            $this->priceRuleLexemeHandler
        );

        $this->priceList = $this->createPriceList();

        // Need call this event first, to set initial state
        $this->listener->beforeSubmit($this->createFormProcessEvent($this->priceList));
    }

    public function testPostSubmit()
    {
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $event = new AfterFormProcessEvent($form, $this->priceList);

        $this->priceRuleLexemeHandler->expects($this->once())
            ->method('updateLexemes')
            ->with($this->priceList);

        $this->listener->onPostSubmit($event);
    }

    public function testWithoutChanges()
    {
        $this->builder->expects($this->never())
            ->method('buildByPriceList');

        $this->triggerHandler->expects($this->never())
            ->method('handlePriceListStatusChange');

        $this->listener->afterFlush($this->createAfterFormProcessEvent($this->priceList));
    }

    public function testStatusChanged()
    {
        $this->priceList->setActive(false);
        $this->triggerHandler->expects($this->once())
            ->method('handlePriceListStatusChange');

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
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\PriceList', ['id' => 1]);
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

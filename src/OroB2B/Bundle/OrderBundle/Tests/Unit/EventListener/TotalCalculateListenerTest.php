<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormFactory;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use OroB2B\Bundle\OrderBundle\EventListener\TotalCalculateListener;

class TotalCalculateListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactory */
    protected $formFactory;

    /** @var TotalCalculateListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()->getMock();

        $this->listener = new TotalCalculateListener($this->formFactory);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formFactory, $this->listener);
    }

    public function testOnBeforeTotalCalculate()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Form\Type\OrderType')
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())->method('submit');

        $this->formFactory->expects($this->once())->method('create')->willReturn($form);

        $entity = new Order();
        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testUnSupportedEntity()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('OroB2B\Bundle\OrderBundle\Form\Type\OrderType')
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->never())->method('submit');

        $this->formFactory->expects($this->never())->method('create')->willReturn($form);

        $entity = new OrderLineItem();
        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->listener->onBeforeTotalCalculate($event);
    }
}

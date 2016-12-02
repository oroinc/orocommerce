<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormFactory;

use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\OrderBundle\EventListener\TotalCalculateListener;

class TotalCalculateListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactory */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CurrentApplicationProviderInterface */
    protected $applicationProvider;

    /** @var TotalCalculateListener */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactory')
            ->disableOriginalConstructor()->getMock();

        $this->applicationProvider = $this->getMock(CurrentApplicationProviderInterface::class);

        $this->listener = new TotalCalculateListener($this->formFactory, $this->applicationProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formFactory, $this->listener, $this->applicationProvider);
    }

    /**
     * @dataProvider testOnBeforeTotalCalculateProvider
     *
     * @param $application
     * @param $expected
     */
    public function testOnBeforeTotalCalculate($application, $expected)
    {
        $this->applicationProvider->expects($this->once())->method('getCurrentApplication')->willReturn($application);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Oro\Bundle\OrderBundle\Form\Type\OrderType')
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->once())->method('submit');

        $this->formFactory->expects($this->once())->method('create')->willReturn($form);

        $entity = $this->getMock('Oro\Bundle\OrderBundle\Entity\Order');

        if ($expected['resetDiscounts']) {
            $entity->expects($this->once())->method('resetDiscounts');
        } else {
            $entity->expects($this->never())->method('resetDiscounts');
        }
        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->listener->onBeforeTotalCalculate($event);
    }

    /**
     * @return array
     */
    public function testOnBeforeTotalCalculateProvider()
    {
        return [
            'application default' => [
                'application' => 'default',
                'expected' => [
                    'resetDiscounts' => true
                ]
            ]
        ];
    }

    public function testOnBeforeTotalCalculateUnexpectedApplication()
    {
        $application  = 'unexpected application';
        $this->applicationProvider->expects($this->once())->method('getCurrentApplication')->willReturn($application);

        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Oro\Bundle\OrderBundle\Form\Type\OrderType')
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->never())->method('submit');

        $entity = new Order();
        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->listener->onBeforeTotalCalculate($event);
    }

    public function testUnSupportedEntity()
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Oro\Bundle\OrderBundle\Form\Type\OrderType')
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

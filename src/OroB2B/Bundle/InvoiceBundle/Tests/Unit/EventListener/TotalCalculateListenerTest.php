<?php

namespace Oro\Bundle\InvoiceBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormFactory;

use Oro\Bundle\InvoiceBundle\Entity\Invoice;
use Oro\Bundle\InvoiceBundle\Entity\InvoiceLineItem;
use Oro\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;
use Oro\Bundle\InvoiceBundle\EventListener\TotalCalculateListener;

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
     * @dataProvider getDataProvider
     *
     * @param object $entity
     * @param int $createFormAmount
     * @param int $executeSubmitAmount
     */
    public function testOnBeforeTotalCalculate($entity, $createFormAmount, $executeSubmitAmount)
    {
        $request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $form = $this->getMockBuilder('Oro\Bundle\OrderBundle\Form\Type\OrderType')
            ->setMethods(['submit'])
            ->disableOriginalConstructor()
            ->getMock();

        $form->expects($this->exactly($executeSubmitAmount))->method('submit');

        $this->formFactory->expects($this->exactly($createFormAmount))->method('create')->willReturn($form);

        $event = new TotalCalculateBeforeEvent($entity, $request);

        $this->listener->onBeforeTotalCalculate($event);
    }

    /**
     * @return array
     */
    public function getDataProvider()
    {
        return [
            'supportedClass' => [
                'entity' => new Invoice(),
                'createFormAmount' => 1,
                'executeSubmitAmount' => 1
            ],
            'unSupportedClass'=>[
                'entity' => new InvoiceLineItem(),
                'createFormAmount' => 0,
                'executeSubmitAmount' => 0
            ]

        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->formFactory, $this->listener);
    }
}

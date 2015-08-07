<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\EventListener\LineItemSubscriber;
use OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager;

class LineItemSubscriberTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_CLASS = 'OroB2BProductBundle:Product';

    public function testGetSubscribedEvents()
    {
        $events = LineItemSubscriber::getSubscribedEvents();
        $expectedEvents = [FormEvents::PRE_SUBMIT];

        $this->assertEquals(array_keys($events), $expectedEvents);
    }

    public function testPreSubmitEmptyData()
    {
        $lineItem = new LineItem();
        $event = $this->getEvent($lineItem);

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemManager $lineItemManager */
        $lineItemManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager')
            ->disableOriginalConstructor()
            ->getMock();

        $lineItemManager->expects($this->never())
            ->method('roundProductQuantity');

        $registry = $this->getRegistry();
        $lineItemSubscriber = new LineItemSubscriber($lineItemManager, $registry);

        $event->expects($this->any())
            ->method('getData')
            ->willReturn([
                'product' => 1,
                'unit' => null,
                'quantity' => null
            ]);


        $lineItemSubscriber->preSubmit($event);
    }

    public function testPreSubmitProductInData()
    {
        $lineItem = new LineItem();
        $event = $this->getEvent($lineItem);

        $lineItemManager = $this->getLineItemManager();

        $registry = $this->getRegistry();

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $registry->getManagerForClass(self::PRODUCT_CLASS)->getRepository(self::PRODUCT_CLASS);
        $repository->expects($this->any())
            ->method('find')
            ->willReturn($this->getProductEntityWithPrecision('item', 3));

        $lineItemSubscriber = new LineItemSubscriber($lineItemManager, $registry);
        $event->expects($this->any())
            ->method('getData')
            ->willReturn([
                'product' => 1,
                'unit' => 'item',
                'quantity' => 1.1119
            ]);

        $event->expects($this->any())
            ->method('setData')
            ->with([
                'product' => 1,
                'unit' => 'item',
                'quantity' => 1.112
            ]);

        $lineItemSubscriber->preSubmit($event);
    }

    public function testPreSubmitProductInFormData()
    {
        $lineItem = new LineItem();
        $event = $this->getEvent($lineItem);
        $lineItemManager = $this->getLineItemManager();
        $registry = $this->getRegistry();

        $lineItemSubscriber = new LineItemSubscriber($lineItemManager, $registry);
        $event->expects($this->any())
            ->method('getData')
            ->willReturn([
                'product' => null,
                'unit' => 'item',
                'quantity' => 1.1119
            ]);

        $event->expects($this->any())
            ->method('setData')
            ->with([
                'product' => null,
                'unit' => 'item',
                'quantity' => 1.112
            ]);

        $lineItem->setProduct($this->getProductEntityWithPrecision('item', 3));
        $lineItemSubscriber->preSubmit($event);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    public function getRegistry()
    {
        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $manager */
        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        return $registry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LineItemManager
     */
    protected function getLineItemManager()
    {
        $lineItemManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\LineItemManager')
            ->disableOriginalConstructor()
            ->getMock();
        $lineItemManager->expects($this->any())
            ->method('roundProductQuantity')
            ->willReturnCallback(
                function ($product, $unit, $quantity) {
                    /** @var \PHPUnit_Framework_MockObject_MockObject|Product $product */
                    return round($quantity, $product->getUnitPrecision($unit)->getPrecision());
                }
            );

        return $lineItemManager;
    }

    /**
     * @param LineItem $formData
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormEvent
     */
    protected function getEvent(LineItem $formData)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormEvent $event */
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->willReturn($formData);

        $event->expects($this->any())
            ->method('getForm')
            ->willReturn($form);

        return $event;
    }

    /**
     * @param string  $unitCode
     * @param integer $precision
     *
     * @return Product
     */
    protected function getProductEntityWithPrecision($unitCode, $precision = 0)
    {
        $product = new Product();

        $unit = new ProductUnit();
        $unit->setCode($unitCode);

        $unitPrecision = new ProductUnitPrecision();
        $unitPrecision
            ->setPrecision($precision)
            ->setUnit($unit)
            ->setProduct($product);

        return $product->addUnitPrecision($unitPrecision);
    }
}

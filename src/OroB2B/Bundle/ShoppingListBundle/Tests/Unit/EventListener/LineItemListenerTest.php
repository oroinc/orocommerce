<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\LineItemListener;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShoppingList
     */
    protected $shoppingList;

    /**
     * @var LineItem
     */
    protected $lineItem;

    public function setUp()
    {
        $this->shoppingList = (new ShoppingList())
            ->setCurrent(true)
            ->setAccountUser(new AccountUser());
        $this->lineItem = (new LineItem())->setShoppingList($this->shoppingList);
        $this->shoppingList->addLineItem($this->lineItem);
    }

    public function testPostRemove()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($shoppingListManager);

        $shoppingListManager->expects($this->once())
            ->method('recalculateSubtotals')
            ->willReturn($this->shoppingList);

        $listener = new LineItemListener($shoppingListManagerLink);
        $lifecycleEventArgs = $this->getEventArgs($this->lineItem);
        $listener->postRemove($lifecycleEventArgs);
    }

    public function testPostRemoveUnsupportedEntity()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListManagerLink->expects($this->never())
            ->method('getService')
            ->willReturn($shoppingListManager);

        $shoppingListManager->expects($this->never())
            ->method('recalculateSubtotals')
            ->willReturn($this->shoppingList);

        $listener = new LineItemListener($shoppingListManagerLink);
        $lifecycleEventArgs = $this->getEventArgs($this->shoppingList);
        $listener->postRemove($lifecycleEventArgs);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs
     */
    protected function getEventArgs($entity)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        return $lifecycleEventArgs;
    }
}

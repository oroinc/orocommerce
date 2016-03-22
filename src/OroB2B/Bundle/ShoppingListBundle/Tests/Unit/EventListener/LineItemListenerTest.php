<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\LineItemListener;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShoppingList */
    protected $shoppingList;

    /** @var LineItem */
    protected $lineItem;

    public function setUp()
    {
        $this->shoppingList = (new ShoppingList())
            ->setCurrent(true)
            ->setAccountUser(new AccountUser());
        $this->lineItem = (new LineItem())->setShoppingList($this->shoppingList);
        $this->shoppingList->addLineItem($this->lineItem);
    }

    public function testPostFlush()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListManagerLink->expects($this->once())
            ->method('getService')
            ->willReturn($shoppingListManager);
        $shoppingListManager->expects($this->once())
            ->method('recalculateSubtotals')
            ->willReturn($this->shoppingList);

        $postFlushEventArgs = $this->preparePostFlushEvent(1);

        $listener = new LineItemListener($shoppingListManagerLink);
        $listener->addShoppingList($this->shoppingList);
        $this->assertNotEmpty($listener->getShoppingLists());
        $listener->postFlush($postFlushEventArgs);
        $this->assertEmpty($listener->getShoppingLists());
    }

    public function testPostRemoveNoShoppingList()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager $shoppingListManager */
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListManagerLink->expects($this->never())
            ->method('getService')
            ->willReturn($shoppingListManager);

        $shoppingListManager->expects($this->never())
            ->method('recalculateSubtotals')
            ->willReturn($this->shoppingList);

        $postFlushEventArgs = $this->preparePostFlushEvent();

        $listener = new LineItemListener($shoppingListManagerLink);
        $this->assertEmpty($listener->getShoppingLists());
        $listener->postFlush($postFlushEventArgs);
        $this->assertEmpty($listener->getShoppingLists());
    }

    public function testOnFlush()
    {
        $deletedLineItems = [$this->lineItem];

        list($onFlushEventArgs, $listener) = $this->prepareLineItemListenerOnFlush($deletedLineItems);

        $this->assertEmpty($listener->getShoppingLists());
        $listener->onFlush($onFlushEventArgs);
        $this->assertNotEmpty($listener->getShoppingLists());
    }

    public function testOnFlushUnsupportedEntity()
    {
        $deletedLineItems = [new \stdClass()];

        list($onFlushEventArgs, $listener) = $this->prepareLineItemListenerOnFlush($deletedLineItems);

        $this->assertEmpty($listener->getShoppingLists());
        $listener->onFlush($onFlushEventArgs);
        $this->assertEmpty($listener->getShoppingLists());
    }

    public function testOnFlushSkippOnRemoveShoppingListEntity()
    {
        $deletedLineItems = [$this->lineItem, $this->shoppingList];

        list($onFlushEventArgs, $listener) = $this->prepareLineItemListenerOnFlush($deletedLineItems);

        $this->assertEmpty($listener->getShoppingLists());
        $listener->onFlush($onFlushEventArgs);
        $this->assertEmpty($listener->getShoppingLists());
    }

    public function testAddShoppingList()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new LineItemListener($shoppingListManagerLink);

        $this->assertEmpty($listener->getShoppingLists());
        $listener->addShoppingList($shoppingList);
        $this->assertNotEmpty($listener->getShoppingLists());
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

    /**
     * @param $deletedLineItems
     *
     * @return array
     */
    protected function prepareLineItemListenerOnFlush($deletedLineItems)
    {
        $uow = $this->getMockBuilder('Oro\Component\TestUtils\ORM\Mocks\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|OnFlushEventArgs $onFlushEventArgs */
        $onFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\OnFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));
        $onFlushEventArgs
            ->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $uow
            ->expects($this->any())
            ->method('getScheduledEntityDeletions')
            ->will($this->returnValue($deletedLineItems));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ServiceLink $shoppingListManagerLink */
        $shoppingListManagerLink = $this->getMockBuilder('Oro\Component\DependencyInjection\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new LineItemListener($shoppingListManagerLink);

        return array($onFlushEventArgs, $listener);
    }

    /**
     * @param int $expectedCount
     *
     * @return PostFlushEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function preparePostFlushEvent($expectedCount = 0)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|PostFlushEventArgs $postFlushEventArgs */
        $postFlushEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\PostFlushEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $postFlushEventArgs
            ->expects($this->exactly($expectedCount))
            ->method('getEntityManager')
            ->will($this->returnValue($em));
        $em
            ->expects($this->exactly($expectedCount))
            ->method('flush');

        return $postFlushEventArgs;
    }
}

<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\UnitOfWork;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\ShoppingListListener;

class ShoppingListListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ShoppingList
     */
    protected $shoppingListOne;
    /**
     * @var ShoppingList
     */
    protected $shoppingListTwo;

    public function setUp()
    {
        $this->shoppingListOne = (new ShoppingList())
            ->setCurrent(true)
            ->setOwner(new AccountUser());
        $this->shoppingListTwo = new ShoppingList();
    }

    public function testPostRemove()
    {
        $listener = new ShoppingListListener();
        $lifecycleEventArgs = $this->getEventArgs();
        $listener->postRemove($lifecycleEventArgs);
        $this->assertTrue($this->shoppingListTwo->isCurrent());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs
     */
    protected function getEventArgs()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LifecycleEventArgs $lifecycleEventArgs */
        $lifecycleEventArgs = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntity')
            ->willReturn($this->shoppingListOne);
        $lifecycleEventArgs->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->getEntityManager());

        return $lifecycleEventArgs;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected function getEntityManager()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|UnitOfWork $uow */
        $uow = $this->getMockBuilder('Doctrine\ORM\UnitOfWork')->disableOriginalConstructor()->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository $repository */
        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['findLatestForAccountUserExceptCurrent'])
            ->getMock();
        $repository->expects($this->once())
            ->method('findLatestForAccountUserExceptCurrent')
            ->willReturn($this->shoppingListTwo);

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $em->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->willReturn($repository);

        return $em;
    }
}

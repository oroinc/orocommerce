<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Oro\Bundle\AccountBundle\Entity\EntityListener\CategoryListener;
use Oro\Bundle\AccountBundle\Visibility\Cache\Product\CacheBuilder;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;

class CategoryListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CategoryListener */
    protected $listener;

    /** @var Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var InsertFromSelectQueryExecutor|\PHPUnit_Framework_MockObject_MockObject */
    protected $insertFromSelectQueryExecutor;

    /** @var CacheBuilder */
    protected $cacheBuilder;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->insertFromSelectQueryExecutor = $this
            ->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheBuilder = $this
            ->getMockBuilder(CacheBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CategoryListener(
            $this->registry,
            $this->insertFromSelectQueryExecutor,
            $this->cacheBuilder
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->registry, $this->insertFromSelectQueryExecutor, $this->listener);
    }

    public function testPostRemove()
    {
        $entity = new Category();

        /** @var LifecycleEventArgs|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Doctrine\ORM\Event\LifecycleEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $productVisibilityRepository = $this
            ->getMockBuilder('Oro\Bundle\AccountBundle\Entity\Visibility\Repository\ProductVisibilityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $productVisibilityEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $productVisibilityEm->expects($this->once())
            ->method('getRepository')
            ->with('OroAccountBundle:Visibility\ProductVisibility')
            ->will($this->returnValue($productVisibilityRepository));

        $accountGroupProductVisibilityRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountGroupProductVisibilityRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $accountGroupProductVisibilityEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $accountGroupProductVisibilityEm->expects($this->once())
            ->method('getRepository')
            ->with('OroAccountBundle:Visibility\AccountGroupProductVisibility')
            ->will($this->returnValue($accountGroupProductVisibilityRepository));

        $accountProductVisibilityRepository = $this
            ->getMockBuilder(
                'Oro\Bundle\AccountBundle\Entity\Visibility\Repository\AccountProductVisibilityRepository'
            )
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityRepository->expects($this->once())
            ->method('setToDefaultWithoutCategory');

        $accountProductVisibilityEm = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $accountProductVisibilityEm->expects($this->once())
            ->method('getRepository')
            ->with('OroAccountBundle:Visibility\AccountProductVisibility')
            ->will($this->returnValue($accountProductVisibilityRepository));

        $this->registry->expects($this->exactly(3))
            ->method('getManagerForClass')
            ->withConsecutive(
                ['OroAccountBundle:Visibility\ProductVisibility'],
                ['OroAccountBundle:Visibility\AccountGroupProductVisibility'],
                ['OroAccountBundle:Visibility\AccountProductVisibility']
            )
            ->willReturnOnConsecutiveCalls(
                $productVisibilityEm,
                $accountGroupProductVisibilityEm,
                $accountProductVisibilityEm
            );


        $this->listener->postRemove($event);
    }
}

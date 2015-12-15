<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryPosition;
use OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

class ChangeCategoryPositionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChangeCategoryPosition
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new ChangeCategoryPosition($contextAccessor);
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage CacheBuilder is not provided
     */
    public function testInitializeFailed()
    {
        $this->action->initialize([]);
    }

    public function testExecuteAction()
    {
        $category = new Category();

        /** @var CategoryCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $cacheBuilder */
        $cacheBuilder = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        /** @var Registry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('transactional')
            ->willReturnCallback(
                function ($callback) {
                    call_user_func($callback);
                }
            );

        $cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category);

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($em);

        $this->action->setCacheBuilder($cacheBuilder);
        $this->action->setRegistry($registry);
        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $category]));
    }
}

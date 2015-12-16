<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\RegistryInterface;
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

    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CategoryCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new ChangeCategoryPosition($contextAccessor);
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        $this->action->setDispatcher($dispatcher);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
    }

    public function testExecuteAction()
    {
        $category = new Category();

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

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroB2BAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($em);

        $this->cacheBuilder->expects($this->once())
            ->method('categoryPositionChanged')
            ->with($category);

        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $category]));
    }
}

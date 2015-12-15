<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder;
use OroB2B\Bundle\AccountBundle\Model\Action\ChangeCategoryVisibility;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;

class ChangeCategoryVisibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ChangeCategoryVisibility
     */
    protected $action;

    protected function setUp()
    {
        $contextAccessor = new ContextAccessor();
        $this->action    = new ChangeCategoryVisibility($contextAccessor);
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
        $categoryVisibility = new CategoryVisibility();

        /** @var CacheBuilder|\PHPUnit_Framework_MockObject_MockObject $cacheBuilder */
        $cacheBuilder = $this->getMock('OroB2B\Bundle\AccountBundle\Visibility\Cache\Product\Category\CacheBuilder');
        $cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($categoryVisibility);

        $this->action->setCacheBuilder($cacheBuilder);
        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $categoryVisibility]));
    }
}

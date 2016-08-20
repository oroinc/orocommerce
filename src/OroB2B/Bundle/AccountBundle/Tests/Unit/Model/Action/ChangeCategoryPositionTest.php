<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\AccountBundle\Model\Action\ChangeCategoryPosition;
use Oro\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

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
            ->getMock('Oro\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        $this->action->setDispatcher($dispatcher);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
    }

    /**
     * @dataProvider executeActionDataProvider
     * @param bool $throwException
     */
    public function testExecuteAction($throwException = false)
    {
        $category = new Category();

        /** @var CategoryCaseCacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $cacheBuilder */
        $cacheBuilder = $this
            ->getMock('Oro\Bundle\AccountBundle\Visibility\Cache\CategoryCaseCacheBuilderInterface');

        /** @var Registry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $cacheBuilder->expects($this->once())
                ->method('categoryPositionChanged')
                ->with($category)
                ->will($this->throwException(new \Exception('Error')));

            $em->expects($this->once())
                ->method('rollback');

            $this->setExpectedException('\Exception', 'Error');
        } else {
            $cacheBuilder->expects($this->once())
                ->method('categoryPositionChanged')
                ->with($category);

            $em->expects($this->once())
                ->method('commit');
        }

        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($em);

        $this->action->setCacheBuilder($cacheBuilder);
        $this->action->setRegistry($registry);
        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $category]));
    }

    /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        return [
            [
                'throwException' => true
            ],
            [
                'throwException' => false
            ],
        ];
    }
}

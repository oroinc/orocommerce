<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\AccountBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\AccountBundle\Model\Action\ResolveProductVisibility;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;

class ResolveProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    /**
     * @var ResolveProductVisibility
     */
    protected $action;

    protected function setUp()
    {
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this->getMock('Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface');
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $contextAccessor = new ContextAccessor();
        $this->action = new ResolveProductVisibility($contextAccessor);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
        $this->action->setDispatcher($eventDispatcher);
    }

    /**
     * @param bool $throwException
     * @dataProvider executeActionDataProvider
     */
    public function testExecute($throwException = false)
    {
        $entity = new ProductVisibility();

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $this->cacheBuilder->expects($this->once())
                ->method('resolveVisibilitySettings')
                ->with($entity)
                ->will($this->throwException(new \Exception('Error')));

            $em->expects($this->once())
                ->method('rollback');

            $this->setExpectedException('\Exception', 'Error');
        } else {
            $this->cacheBuilder->expects($this->once())
                ->method('resolveVisibilitySettings')
                ->with($entity);

            $em->expects($this->once())
                ->method('commit');
        }

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($em);

        $this->cacheBuilder->expects($this->once())
            ->method('resolveVisibilitySettings')
            ->with($entity);

        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $entity]));
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

<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\AccountBundle\Model\Action\BuildWebsiteCacheAction;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class BuildWebsiteCacheActionTest extends \PHPUnit_Framework_TestCase
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
     * @var BuildWebsiteCacheAction
     */
    protected $action;

    protected function setUp()
    {
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this->getMock(
            'Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface'
        );
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $contextAccessor = new ContextAccessor();
        $this->action = new BuildWebsiteCacheAction($contextAccessor);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
        $this->action->setDispatcher($eventDispatcher);
    }

    /**
     * @dataProvider executeActionDataProvider
     * @param bool $throwException
     */
    public function testExecute($throwException)
    {
        $entity = new Website();
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $this->cacheBuilder->expects($this->once())
                ->method('buildCache')
                ->with($entity)
                ->will($this->throwException(new \Exception('Error')));
            $entityManager->expects($this->once())
                ->method('rollback');
            $this->setExpectedException('\Exception', 'Error');
        } else {
            $this->cacheBuilder->expects($this->once())
                ->method('buildCache')
                ->with($entity);
            $entityManager->expects($this->once())
                ->method('commit');
        }
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($entityManager);

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

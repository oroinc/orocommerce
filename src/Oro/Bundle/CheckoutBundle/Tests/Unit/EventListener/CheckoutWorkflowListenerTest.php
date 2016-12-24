<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutWorkflowListener;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutWorkflowListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const ENTITY_CLASS = 'stdClass';

    /** @var CheckoutRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var CheckoutWorkflowListener */
    protected $listener;

    protected function setUp()
    {
        $this->repository = $this->getMockBuilder(CheckoutRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->listener = new CheckoutWorkflowListener($registry, self::ENTITY_CLASS);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->workflowScopeManager);
    }

    public function testOnDeactivationWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->repository->expects($this->once())->method('deleteWithoutWorkflowItem');

        $this->listener->onDeactivationWorkflowDefinition($event);
    }

    public function testOnDeactivationWorkflowDefinitionForUnsupportedEntity()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition('UnknownEntity'));

        $this->repository->expects($this->never())->method('deleteWithoutWorkflowItem');

        $this->listener->onDeactivationWorkflowDefinition($event);
    }

    /**
     * @param string $entityClass
     * @return WorkflowDefinition
     */
    protected function createWorkflowDefinition($entityClass = self::ENTITY_CLASS)
    {
        return $this->getEntity(WorkflowDefinition::class, ['relatedEntity' => $entityClass]);
    }
}

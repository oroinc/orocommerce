<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutWorkflowListener;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;

class CheckoutWorkflowListenerTest extends \PHPUnit\Framework\TestCase
{
    private const ENTITY_CLASS = 'stdClass';

    /** @var CheckoutRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CheckoutWorkflowListener */
    private $listener;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CheckoutRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(self::ENTITY_CLASS)
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($manager);

        $this->listener = new CheckoutWorkflowListener($doctrine, self::ENTITY_CLASS);
    }

    public function testOnDeactivationWorkflowDefinition()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition());

        $this->repository->expects($this->once())
            ->method('deleteWithoutWorkflowItem');

        $this->listener->onDeactivationWorkflowDefinition($event);
    }

    public function testOnDeactivationWorkflowDefinitionForUnsupportedEntity()
    {
        $event = new WorkflowChangesEvent($this->createWorkflowDefinition('UnknownEntity'));

        $this->repository->expects($this->never())
            ->method('deleteWithoutWorkflowItem');

        $this->listener->onDeactivationWorkflowDefinition($event);
    }

    private function createWorkflowDefinition(string $entityClass = self::ENTITY_CLASS): WorkflowDefinition
    {
        $workflowDefinition = new WorkflowDefinition();
        $workflowDefinition->setRelatedEntity($entityClass);

        return $workflowDefinition;
    }
}

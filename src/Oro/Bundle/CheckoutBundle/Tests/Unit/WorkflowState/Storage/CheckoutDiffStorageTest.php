<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Storage;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use Oro\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorage;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CheckoutDiffStorageTest extends \PHPUnit\Framework\TestCase
{
    private const STORAGE_ENTITY_CLASS = CheckoutWorkflowState::class;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var CheckoutDiffStorage */
    private $storage;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->storage = new CheckoutDiffStorage($this->doctrineHelper, self::STORAGE_ENTITY_CLASS);
    }

    private function assertStorageEntity(CheckoutWorkflowState $entity): bool
    {
        $this->isInstanceOf(self::STORAGE_ENTITY_CLASS);
        $this->assertEquals(['someKey' => 'someValue'], $entity->getStateData());
        $this->assertEquals(7, $entity->getEntityId());
        $this->assertEquals('stdClass', $entity->getEntityClass());

        return true;
    }

    public function testAddState()
    {
        $entity = new \stdClass();
        $entityId = 7;
        $data = ['someKey' => 'someValue'];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('stdClass');

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (CheckoutWorkflowState $entity) {
                return $this->assertStorageEntity($entity);
            }));

        $em->expects($this->once())
            ->method('flush')
            ->with($this->callback(function (CheckoutWorkflowState $entity) {
                return $this->assertStorageEntity($entity);
            }));

        $this->assertNotEmpty($this->storage->addState($entity, $data));
    }

    public function testAddStateWithToken()
    {
        $entity = new \stdClass();
        $entityId = 7;
        $data = ['someKey' => 'someValue'];

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('stdClass');

        $em = $this->createMock(EntityManagerInterface::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (CheckoutWorkflowState $entity) {
                return $this->assertStorageEntity($entity);
            }));

        $em->expects($this->once())
            ->method('flush')
            ->with($this->callback(function (CheckoutWorkflowState $entity) {
                return $this->assertStorageEntity($entity);
            }));

        $expectedToken = 'expectedToken';

        $this->assertEquals($expectedToken, $this->storage->addState($entity, $data, ['token' => $expectedToken]));
    }

    public function testReadStateWhenDataExists()
    {
        $entityId = 7;
        $entity = new \stdClass();
        $token = 'unique_token_1';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('stdClass');
        $repository = $this->createMock(CheckoutWorkflowStateRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $expectedData = ['someKey' => 'someValue'];
        $storageEntity = $this->createMock(self::STORAGE_ENTITY_CLASS);

        $storageEntity->expects($this->once())
            ->method('getStateData')
            ->willReturn($expectedData);

        $repository->expects($this->once())
            ->method('getEntityByToken')
            ->with($entityId, get_class($entity), $token)
            ->willReturn($storageEntity);

        $this->assertEquals($expectedData, $this->storage->getState($entity, $token));
    }

    public function testReadStateWhenNoDataExists()
    {
        $entityId = 7;
        $entity = new \stdClass();
        $token = 'unique_token_1';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('stdClass');
        $repository = $this->createMock(CheckoutWorkflowStateRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('getEntityByToken')
            ->with($entityId, get_class($entity), $token)
            ->willReturn(null);

        $this->assertEquals([], $this->storage->getState($entity, $token));
    }

    public function testDeleteStates()
    {
        $entityId = 7;
        $entity = new \stdClass();

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($entityId);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturn('stdClass');
        $repository = $this->createMock(CheckoutWorkflowStateRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('deleteEntityStates')
            ->with($entityId, get_class($entity));

        $this->storage->deleteStates($entity);
    }
}

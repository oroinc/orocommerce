<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Storage;

use Doctrine\ORM\EntityManagerInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState;
use OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository;
use OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\CheckoutDiffStorage;

class CheckoutDiffStorageTest extends \PHPUnit_Framework_TestCase
{
    const STORAGE_ENTITY_CLASS = 'OroB2B\Bundle\CheckoutBundle\Entity\CheckoutWorkflowState';

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var CheckoutDiffStorage
     */
    private $storage;

    public function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new CheckoutDiffStorage($this->doctrineHelper, self::STORAGE_ENTITY_CLASS);
    }

    /**
     * @param CheckoutWorkflowState $entity
     * @return bool
     */
    public function assertStorageEntity(CheckoutWorkflowState $entity)
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

        $this->prepareSingleEntityIdentifier($entityId);
        $this->prepareEntityClass('stdClass');

        /** @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMock('\Doctrine\ORM\EntityManagerInterface');

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

    public function testReadStateWhenDataExists()
    {
        $entityId = 7;
        $entity = new \stdClass();
        $token = 'unique_token_1';

        $this->prepareSingleEntityIdentifier($entityId);
        $this->prepareEntityClass('stdClass');
        $repository = $this->prepareEntityRepository();

        $expectedData = ['someKey' => 'someValue'];
        $storageEntity = $this->getMockBuilder(self::STORAGE_ENTITY_CLASS)
            ->getMock();

        $storageEntity->expects($this->once())
            ->method('getStateData')
            ->willReturn($expectedData);

        $repository->expects($this->once())
            ->method('getEntityByToken')
            ->with($entityId, get_class($entity), $token)
            ->willReturn($storageEntity);

        $this->assertEquals($expectedData, $this->storage->readState($entity, $token));
    }

    public function testReadStateWhenNoDataExists()
    {
        $entityId = 7;
        $entity = new \stdClass();
        $token = 'unique_token_1';

        $this->prepareSingleEntityIdentifier($entityId);
        $this->prepareEntityClass('stdClass');
        $repository = $this->prepareEntityRepository();

        $repository->expects($this->once())
            ->method('getEntityByToken')
            ->with($entityId, get_class($entity), $token)
            ->willReturn(null);

        $this->assertEquals([], $this->storage->readState($entity, $token));
    }

    public function testDeleteStates()
    {
        $entityId = 7;
        $entity = new \stdClass();

        $this->prepareSingleEntityIdentifier($entityId);
        $this->prepareEntityClass('stdClass');
        $repository = $this->prepareEntityRepository();

        $repository->expects($this->once())
            ->method('deleteEntityStates')
            ->with($entityId, get_class($entity));

        $this->storage->deleteStates($entity);
    }

    /**
     * @param int $id
     */
    protected function prepareSingleEntityIdentifier($id)
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($id);
    }

    /**
     * @param string $class
     */
    protected function prepareEntityClass($class)
    {
        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->willReturn($class);
    }

    /**
     * @return CheckoutWorkflowStateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareEntityRepository()
    {
        $repository = $this->entityRepository = $this->getMockBuilder(
            'OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->willReturn($repository);

        return $repository;
    }
}

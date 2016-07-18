<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\WorkflowState\Storage;

use Doctrine\ORM\EntityManager;

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
     * @var CheckoutWorkflowStateRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityRepository;

    /**
     * @var EntityManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManager;

    /**
     * @var CheckoutDiffStorage
     */
    private $storage;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->storage = new CheckoutDiffStorage($this->entityManager, $this->doctrineHelper);
    }

    /**
     * @param CheckoutWorkflowState $item
     * @param array $data
     * @param int $entityId
     * @return bool
     */
    public function assertStorageEntityPersisted($item, $data, $entityId)
    {
        /** @var CheckoutWorkflowState $item */
        $this->isInstanceOf(self::STORAGE_ENTITY_CLASS);
        $this->assertEquals($data, $item->getStateData());
        $this->assertEquals($entityId, $item->getEntityId());
        $this->assertEquals('stdClass', $item->getEntityClass());
        return true;
    }

    public function testAddState()
    {
        $entity = new \stdClass();
        $entityId = 7;
        $data = ['someKey' => 'someValue'];

        $this->setSingleEntityIdentifier($entityId);

        $that = $this;
        $assertionCallback = function ($item) use ($that, $data, $entityId) {
            return $that->assertStorageEntityPersisted($item, $data, $entityId);
        };

        $this->setEntityClass('stdClass');

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback($assertionCallback));

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with($this->callback($assertionCallback));

        $this->assertNotEmpty($this->storage->addState($entity, $data));
    }

    public function testReadStateWhenDataExists()
    {
        $entityId = 7;
        $entity = new \stdClass();
        $token = 'unique_token_1';

        $this->setSingleEntityIdentifier($entityId);
        $this->setEntityClass('stdClass');
        $this->setEntityRepository();

        $expectedData = ['someKey' => 'someValue'];
        $storageEntity = $this->getMockBuilder(self::STORAGE_ENTITY_CLASS)
            ->getMock();

        $storageEntity
            ->expects($this->once())
            ->method('getStateData')
            ->willReturn($expectedData);

        $this->entityRepository
            ->expects($this->once())
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

        $this->setSingleEntityIdentifier($entityId);
        $this->setEntityClass('stdClass');
        $this->setEntityRepository();

        $this->entityRepository
            ->expects($this->once())
            ->method('getEntityByToken')
            ->with($entityId, get_class($entity), $token)
            ->willReturn(null);

        $this->assertEquals([], $this->storage->readState($entity, $token));
    }

    public function testDeleteStates()
    {
        $entityId = 7;
        $entity = new \stdClass();

        $this->setSingleEntityIdentifier($entityId);
        $this->setEntityClass('stdClass');
        $this->setEntityRepository();

        $this->entityRepository
            ->expects($this->once())
            ->method('deleteEntityStates')
            ->with($entityId, get_class($entity));

        $this->storage->deleteStates($entity);
    }

    /**
     * @param int $id
     */
    protected function setSingleEntityIdentifier($id)
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->willReturn($id);
    }

    /**
     * @param string $class
     */
    protected function setEntityClass($class)
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityClass')
            ->willReturn($class);
    }

    protected function setEntityRepository()
    {
        $this->entityRepository = $this->getMockBuilder(
            'OroB2B\Bundle\CheckoutBundle\Entity\Repository\CheckoutWorkflowStateRepository'
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with(self::STORAGE_ENTITY_CLASS)
            ->willReturn($this->entityRepository);
    }
}

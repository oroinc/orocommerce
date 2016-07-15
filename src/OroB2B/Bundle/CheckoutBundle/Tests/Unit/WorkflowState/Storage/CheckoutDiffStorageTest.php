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

    public function testAddState()
    {
        $entity   = new \stdClass();
        $entityId = 7;
        $data     = ['someKey' => 'someValue'];

        $this->setEntityIdentifier([$entityId]);

        $that = $this;
        $assertionCallback = function ($item) use ($that, $data, $entityId) {
            /** @var CheckoutWorkflowState $item */
            $that->isInstanceOf(self::STORAGE_ENTITY_CLASS);
            $that->assertEquals($data, $item->getStateData());
            $that->assertEquals($entityId, $item->getEntityId());
            $that->assertEquals('stdClass', $item->getEntityClass());
            return true;
        };

        $this->entityManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->callback($assertionCallback));

        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->with($this->callback($assertionCallback));

        $this->assertEquals(13, strlen($this->storage->addState($entity, $data)));
    }

    public function testReadStateWhenDataExists()
    {
        $entityId = 7;
        $entity   = new \stdClass();
        $hash     = 'unique_hash_1';

        $this->setEntityIdentifier([$entityId]);
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
            ->method('getEntityByHash')
            ->with($entityId, get_class($entity), $hash)
            ->willReturn($storageEntity);

        $this->assertEquals($expectedData, $this->storage->readState($entity, $hash));
    }

    public function testReadStateWhenNoDataExists()
    {
        $entityId = 7;
        $entity   = new \stdClass();
        $hash     = 'unique_hash_1';

        $this->setEntityIdentifier([$entityId]);
        $this->setEntityRepository();

        $this->entityRepository
            ->expects($this->once())
            ->method('getEntityByHash')
            ->with($entityId, get_class($entity), $hash)
            ->willReturn(null);

        $this->assertEquals([], $this->storage->readState($entity, $hash));
    }

    public function testDeleteStates()
    {
        $entityId = 7;
        $entity   = new \stdClass();

        $this->setEntityIdentifier([$entityId]);
        $this->setEntityRepository();

        $this->entityRepository
            ->expects($this->once())
            ->method('deleteEntityStates')
            ->with($entityId, get_class($entity));

        $this->storage->deleteStates($entity);
    }

    public function badPrimaryKeyProvider()
    {
        return [
            [['sdf']],
            [[1, 1]],
            [['one', 'two']]
        ];
    }

    /**
     * @dataProvider badPrimaryKeyProvider
     *
     * @param array $entityIds
     */
    public function testReadStateWithBadEntity($entityIds)
    {
        $this->setExpectedException(
            '\OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\Exception\NotIntegerPrimaryKeyEntityException'
        );
        $entity = new \stdClass();
        $hash   = 'unique_hash_1';

        $this->setEntityIdentifier($entityIds);
        $this->setEntityRepository();

        $this->storage->readState($entity, $hash);
    }

    /**
     * @dataProvider badPrimaryKeyProvider
     *
     * @param array $entityIds
     */
    public function testAddStateWithBadEntity($entityIds)
    {
        $this->setExpectedException(
            '\OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\Exception\NotIntegerPrimaryKeyEntityException'
        );
        $entity = new \stdClass();

        $this->setEntityIdentifier($entityIds);

        $this->storage->addState($entity, []);
    }

    /**
     * @dataProvider badPrimaryKeyProvider
     *
     * @param array $entityIds
     */
    public function testDeleteStatesWithBadEntity($entityIds)
    {
        $this->setExpectedException(
            '\OroB2B\Bundle\CheckoutBundle\WorkflowState\Storage\Exception\NotIntegerPrimaryKeyEntityException'
        );
        $entity = new \stdClass();

        $this->setEntityIdentifier($entityIds);
        $this->setEntityRepository();

        $this->storage->deleteStates($entity);
    }

    /**
     * @param array $id
     */
    protected function setEntityIdentifier(array $id)
    {
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityIdentifier')
            ->willReturn($id);
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

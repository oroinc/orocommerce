<?php

namespace Oro\Bundle\ApplicationBundle\Tests\Unit\Model;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ApplicationBundle\Event\ModelEvent;
use Oro\Bundle\ApplicationBundle\Event\ModelIdentifierEvent;
use Oro\Bundle\ApplicationBundle\Repository\ModelRepository;
use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel;
use Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestMultiEntityModel;

class ModelRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const MODEL_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestModel';
    const MULTI_ENTITY_MODEL_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestMultiEntityModel';
    const ENTITY_CLASS = 'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestEntity';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ModelFactoryInterface
     */
    protected $modelFactory;

    /**
     * @var ModelRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->modelFactory = $this->getMock('Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface');

        $this->repository = new ModelRepository(
            $this->managerRegistry,
            $this->eventDispatcher,
            $this->modelFactory,
            self::MODEL_CLASS,
            self::ENTITY_CLASS
        );
    }

    protected function tearDown()
    {
        unset($this->managerRegistry, $this->eventDispatcher, $this->modelFactory, $this->repository);
    }

    public function testFindEntityFound()
    {
        $sourceIdentifier = 1;
        $alteredIdentifier = 2;

        $entity = new \DateTime();
        $sourceModel = new TestModel('source');
        $alteredModel = new TestModel('altered');

        // alter identifier in event
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('model.find.before.test_model')
            ->willReturnCallback(
                function($name, ModelIdentifierEvent $event) use ($sourceIdentifier, $alteredIdentifier) {
                    self::assertEquals($sourceIdentifier, $event->getIdentifier());
                    $event->setIdentifier($alteredIdentifier);
                }
            );

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManager->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $alteredIdentifier)
            ->willReturn($entity);
        $objectManager->expects($this->once())
            ->method('initializeObject')
            ->with($entity);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($objectManager);

        $this->modelFactory->expects($this->once())
            ->method('create')
            ->with([$entity])
            ->willReturn($sourceModel);

        // alter model in event
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with('model.find.after.test_model')
            ->willReturnCallback(
                function($name, ModelEvent $event) use ($sourceModel, $alteredModel) {
                    self::assertEquals($sourceModel, $event->getModel());
                    $event->setModel($alteredModel);
                }
            );

        $this->assertEquals($alteredModel, $this->repository->find($sourceIdentifier));
    }

    /**
     * @return array
     */
    public function findEntityNotFoundDataProvider()
    {
        return [
            'not found' => [false],
            'found empty proxy' => [true]
        ];
    }

    /**
     * @param bool $isProxy
     * @dataProvider findEntityNotFoundDataProvider
     */
    public function testFindEntityNotFound($isProxy)
    {
        $identifier = 1;

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('model.find.before.test_model', new ModelIdentifierEvent($identifier));

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        if ($isProxy) {
            $proxy = new \DateTime();
            $objectManager->expects($this->once())
                ->method('find')
                ->with(self::ENTITY_CLASS, $identifier)
                ->willReturn($proxy);
            $objectManager->expects($this->once())
                ->method('initializeObject')
                ->with($proxy)
                ->willThrowException(new EntityNotFoundException());
        } else {
            $objectManager->expects($this->once())
                ->method('find')
                ->with(self::ENTITY_CLASS, $identifier)
                ->willReturn(null);
        }

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($objectManager);

        $this->modelFactory->expects($this->never())
            ->method('create');

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with('model.find.after.not_found.test_model', new ModelIdentifierEvent($identifier));

        $this->assertNull($this->repository->find($identifier));
    }

    public function testSave()
    {
        $model = $this->setEntityTestExpectations('persist', 'save');
        $this->repository->save($model);
    }

    public function testDelete()
    {
        $model = $this->setEntityTestExpectations('remove', 'delete');
        $this->repository->delete($model);
    }

    /**
     * @param string $processMethod
     * @param string $eventSuffix
     * @return TestMultiEntityModel
     */
    protected function setEntityTestExpectations($processMethod, $eventSuffix)
    {
        $firstEntity = new \DateTime('2015-03-03');
        $secondEntity = new \DateTime('2015-04-04');

        $sourceModel = new TestMultiEntityModel(new \DateTime('2015-01-01'), new \DateTime('2015-02-02'));
        $alteredModel = new TestMultiEntityModel($firstEntity, $secondEntity);

        // alter model in event
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with('model.' . $eventSuffix . '.before.test_multi_entity_model')
            ->willReturnCallback(
                function($name, ModelEvent $event) use ($sourceModel, $alteredModel) {
                    self::assertEquals($sourceModel, $event->getModel());
                    $event->setModel($alteredModel);
                }
            );

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManager->expects($this->at(0))
            ->method($processMethod)
            ->with($firstEntity);
        $objectManager->expects($this->at(1))
            ->method($processMethod)
            ->with($secondEntity);
        $objectManager->expects($this->at(2))
            ->method('flush');

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($objectManager);

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with('model.' . $eventSuffix . '.after.test_multi_entity_model', new ModelEvent($alteredModel));

        $this->repository = new ModelRepository(
            $this->managerRegistry,
            $this->eventDispatcher,
            $this->modelFactory,
            self::MULTI_ENTITY_MODEL_CLASS,
            self::ENTITY_CLASS
        );

        return $sourceModel;
    }

    /**
     * @param string $modelClass
     * @param string $entityClass
     * @param string $expectedException
     * @dataProvider constructExceptionsDataProvider
     */
    public function testConstructExceptions($modelClass, $entityClass, $expectedException)
    {
        $this->setExpectedException('\LogicException', $expectedException);

        new ModelRepository(
            $this->managerRegistry,
            $this->eventDispatcher,
            $this->modelFactory,
            $modelClass,
            $entityClass
        );
    }

    /**
     * @return array
     */
    public function constructExceptionsDataProvider()
    {
        return [
            'no model class' => [
                'modelClass' => 'UndefinedModelClass',
                'entityClass' => self::ENTITY_CLASS,
                'expectedException' => 'Class "UndefinedModelClass" is not defined'
            ],
            'invalid model class' => [
                'modelClass' => self::ENTITY_CLASS,
                'entityClass' => self::ENTITY_CLASS,
                'expectedException' => 'Class "' . self::ENTITY_CLASS . '" must implement ModelInterface'
            ],
            'no entity class' => [
                'modelClass' => self::MODEL_CLASS,
                'entityClass' => 'UndefinedEntityClass',
                'expectedException' => 'Class "UndefinedEntityClass" is not defined'
            ],
        ];
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Default repository can create only instances of AbstractModel. You have to create custom repository for model "Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestCustomModel".
     */
    //@codingStandardsIgnoreEnd
    public function testFindNotAbstractEntity()
    {
        $identifier = 1;
        $entity = new \DateTime();

        $objectManager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManager->expects($this->once())
            ->method('find')
            ->with(self::ENTITY_CLASS, $identifier)
            ->willReturn($entity);

        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn($objectManager);

        $repository = new ModelRepository(
            $this->managerRegistry,
            $this->eventDispatcher,
            $this->modelFactory,
            'Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestCustomModel',
            self::ENTITY_CLASS
        );
        $repository->find($identifier);
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Object manager for class "Oro\Bundle\ApplicationBundle\Tests\Unit\Stub\TestEntity" is not defined
     */
    //@codingStandardsIgnoreEnd
    public function testFindNoObjectManager()
    {
        $this->managerRegistry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::ENTITY_CLASS)
            ->willReturn(null);

        $this->repository->find(1);
    }
}

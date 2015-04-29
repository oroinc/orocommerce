<?php

namespace Oro\Bundle\ApplicationBundle\Repository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ApplicationBundle\Factory\ModelFactoryInterface;
use Oro\Bundle\ApplicationBundle\Model\ModelInterface;
use Oro\Bundle\ApplicationBundle\Event\ModelEvent;
use Oro\Bundle\ApplicationBundle\Event\ModelIdentifierEvent;

class ModelRepository implements ModelRepositoryInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var ModelFactoryInterface
     */
    protected $modelFactory;

    /**
     * @var string
     */
    protected $modelClassName;

    /**
     * @var string
     */
    protected $entityClassName;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param EventDispatcherInterface $eventDispatcher
     * @param ModelFactoryInterface $modelFactory
     * @param string $modelClassName
     * @param string $entityClassName
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        EventDispatcherInterface $eventDispatcher,
        ModelFactoryInterface $modelFactory,
        $modelClassName,
        $entityClassName
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->eventDispatcher = $eventDispatcher;
        $this->modelFactory = $modelFactory;

        $this->assertModelClassName($modelClassName);
        $this->assertEntityClassName($entityClassName);

        $this->modelClassName = $modelClassName;
        $this->entityClassName = $entityClassName;
    }

    /**
     * {@inheritdoc}
     */
    public function find($modelIdentifier)
    {
        $modelName = $this->getModelName();

        $identifierEvent = new ModelIdentifierEvent($modelIdentifier);
        $this->eventDispatcher->dispatch($modelName . '.model.find.before', $identifierEvent);

        $objectManager = $this->managerRegistry->getManagerForClass($this->entityClassName);
        if (!$objectManager) {
            throw new \LogicException(sprintf('Object manager for class "%s" is not defined', $this->entityClassName));
        }

        $entity = $objectManager->find($this->entityClassName, $identifierEvent->getIdentifier());

        if ($entity) {
            $this->assertAbstractModelClassName($this->modelClassName);

            $model = $this->modelFactory->create([$entity]);

            $modelEvent = new ModelEvent($model);
            $this->eventDispatcher->dispatch($modelName . '.model.find.after', $modelEvent);

            return $modelEvent->getModel();
        } else {
            $identifierEvent = new ModelIdentifierEvent($modelIdentifier);
            $this->eventDispatcher->dispatch($modelName . '.model.find.after.not_found', $identifierEvent);

            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function save(ModelInterface $model)
    {
        $modelName = $this->getModelName();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch($modelName . '.model.save.before', $modelEvent);

        $model = $modelEvent->getModel();

        $objectManager = $this->managerRegistry->getManagerForClass($this->entityClassName);
        foreach ($model->getEntities() as $entity) {
            $objectManager->persist($entity);
        }
        $objectManager->flush();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch($modelName . '.model.save.after', $modelEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ModelInterface $model)
    {
        $modelName = $this->getModelName();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch($modelName . '.model.delete.before', $modelEvent);

        $model = $modelEvent->getModel();

        $objectManager = $this->managerRegistry->getManagerForClass($this->entityClassName);
        foreach ($model->getEntities() as $entity) {
            $objectManager->remove($entity);
        }
        $objectManager->flush();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch($modelName . '.model.delete.after', $modelEvent);
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        // in fact this is model class name, not model instance, phpdoc is used to support autocomplete
        /** @var ModelInterface $modelClassName */
        $modelClassName = $this->modelClassName;

        return $modelClassName::getModelName();
    }

    /**
     * @param string $modelClassName
     */
    protected function assertModelClassName($modelClassName)
    {
        if (!class_exists($modelClassName)) {
            throw new \LogicException(sprintf('Class "%s" is not defined', $modelClassName));
        }

        if (!in_array('Oro\Bundle\ApplicationBundle\Model\ModelInterface', class_implements($modelClassName))) {
            throw new \LogicException(sprintf('Class "%s" must implement ModelInterface', $modelClassName));
        }
    }

    /**
     * @param string $entityClassName
     */
    protected function assertEntityClassName($entityClassName)
    {
        if (!class_exists($entityClassName)) {
            throw new \LogicException(sprintf('Class "%s" is not defined', $entityClassName));
        }
    }

    /**
     * @param string $modelClassName
     * @return bool
     */
    protected function assertAbstractModelClassName($modelClassName)
    {
        if (!in_array('Oro\Bundle\ApplicationBundle\Model\AbstractModel', class_parents($modelClassName))) {
            throw new \LogicException(
                'Default repository can create only instances of AbstractModel. ' .
                'You have to create custom repository for custom model.'
            );
        }
    }
}

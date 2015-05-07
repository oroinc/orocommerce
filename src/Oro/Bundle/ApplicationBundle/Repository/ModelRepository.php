<?php

namespace Oro\Bundle\ApplicationBundle\Repository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

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
        $this->eventDispatcher->dispatch('model.find.before.' . $modelName, $identifierEvent);

        $entity = $this->getObjectManager()->find($this->entityClassName, $identifierEvent->getIdentifier());

        if ($entity) {
            $this->assertAbstractModelClassName($this->modelClassName);

            $model = $this->modelFactory->create([$entity]);

            $modelEvent = new ModelEvent($model);
            $this->eventDispatcher->dispatch('model.find.after.' . $modelName, $modelEvent);

            return $modelEvent->getModel();
        } else {
            $identifierEvent = new ModelIdentifierEvent($modelIdentifier);
            $this->eventDispatcher->dispatch('model.find.after.not_found.' . $modelName, $identifierEvent);

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
        $this->eventDispatcher->dispatch('model.save.before.' . $modelName, $modelEvent);

        $model = $modelEvent->getModel();

        $objectManager = $this->getObjectManager();
        foreach ($model->getEntities() as $entity) {
            $objectManager->persist($entity);
        }
        $objectManager->flush();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch('model.save.after.' . $modelName, $modelEvent);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ModelInterface $model)
    {
        $modelName = $this->getModelName();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch('model.delete.before.' . $modelName, $modelEvent);

        $model = $modelEvent->getModel();

        $objectManager = $this->getObjectManager();
        foreach ($model->getEntities() as $entity) {
            $objectManager->remove($entity);
        }
        $objectManager->flush();

        $modelEvent = new ModelEvent($model);
        $this->eventDispatcher->dispatch('model.delete.after.' . $modelName, $modelEvent);
    }

    /**
     * @return string
     */
    protected function getModelName()
    {
        /** @var ModelInterface $modelClassName */
        // in fact this is model class name, not model instance, but phpdoc is used to support autocomplete
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

        if (!is_a($modelClassName, 'Oro\Bundle\ApplicationBundle\Model\ModelInterface', true)) {
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
        if (!is_a($modelClassName, 'Oro\Bundle\ApplicationBundle\Model\AbstractModel', true)) {
            throw new \LogicException(
                sprintf(
                    'Default repository can create only instances of AbstractModel. ' .
                    'You have to create custom repository for model "%s".',
                    $modelClassName
                )
            );
        }
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        $objectManager = $this->managerRegistry->getManagerForClass($this->entityClassName);
        if (!$objectManager) {
            throw new \LogicException(sprintf('Object manager for class "%s" is not defined', $this->entityClassName));
        }

        return $objectManager;
    }
}

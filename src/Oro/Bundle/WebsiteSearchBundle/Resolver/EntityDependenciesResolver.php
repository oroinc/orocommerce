<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityDependenciesResolver implements EntityDependenciesResolverInterface
{
    /** @var WebsiteSearchMappingProvider */
    private $mappingProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array */
    private $classesForReindex;

    /** @var array */
    private $classesDependencies;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        WebsiteSearchMappingProvider $mappingProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassesForReindex($class = null)
    {
        if (null === $class) {
            return $this->mappingProvider->getEntityClasses();
        }

        $classes = is_array($class) ? $class : [$class];

        $this->fillClassDependencies();

        $this->classesForReindex = [];
        foreach ($classes as $class) {
            $this->collectDependentClassesForClass($class);
        }

        return array_values($this->classesForReindex);
    }

    private function fillClassDependencies()
    {
        if (null !== $this->classesDependencies) {
            return;
        }

        $collectDependentClassesEvent = new CollectDependentClassesEvent();
        $this->eventDispatcher->dispatch(CollectDependentClassesEvent::NAME, $collectDependentClassesEvent);

        $this->classesDependencies = $collectDependentClassesEvent->getDependencies();
    }

    /**
     * @param string $class
     */
    private function collectDependentClassesForClass($class)
    {
        $this->classesForReindex[$class] = $class;

        if (!array_key_exists($class, $this->classesDependencies)) {
            return;
        }

        foreach ($this->classesDependencies[$class] as $dependentClass) {
            if (!array_key_exists($dependentClass, $this->classesForReindex)) {
                $this->collectDependentClassesForClass($dependentClass);
            }
        }
    }
}

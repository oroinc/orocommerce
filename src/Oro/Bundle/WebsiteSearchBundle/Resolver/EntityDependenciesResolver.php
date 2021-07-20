<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\CollectDependentClassesEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Return class with dependencies for website search mapping.
 */
class EntityDependenciesResolver implements EntityDependenciesResolverInterface
{
    /** @var SearchMappingProvider */
    private $mappingProvider;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var array */
    private $classesForReindex;

    /** @var array */
    private $classesDependencies;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SearchMappingProvider $mappingProvider
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
        $this->eventDispatcher->dispatch($collectDependentClassesEvent, CollectDependentClassesEvent::NAME);

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

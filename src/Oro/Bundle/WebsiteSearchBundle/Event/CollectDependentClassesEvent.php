<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectDependentClassesEvent extends Event
{
    const NAME = 'oro_website_search.event.collect_dependent_classes';

    /** @var array */
    private $dependencies;

    /** @var array */
    private $classesForReindex;

    /**
     * @param array $classes
     * @return array
     */
    public function getClassesForReindex(array $classes)
    {
        $this->classesForReindex = [];
        foreach ($classes as $class) {
            $this->collectDependentClassesForClass($class);
        }

        return array_values($this->classesForReindex);
    }

    /**
     * @param string $class
     */
    private function collectDependentClassesForClass($class)
    {
        $this->classesForReindex[$class] = $class;

        if (isset($this->dependencies[$class])) {

            foreach ($this->dependencies[$class] as $dependentClass) {

                if (!isset($this->classesForReindex[$dependentClass])) {
                    $this->collectDependentClassesForClass($dependentClass);
                }
            }
        }
    }

    /**
     * Adds dependencies for $dependentEntityClass which means that $dependentEntityClass depends on
     * $entityClasses.
     *
     * @param string $dependentEntityClass
     * @param array $entityClasses
     */
    public function addClassDependencies($dependentEntityClass, array $entityClasses)
    {
        foreach ($entityClasses as $entityClass) {
            $this->dependencies[$entityClass][] = $dependentEntityClass;
        }
    }
}

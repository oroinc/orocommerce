<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectDependentClassesEvent extends Event
{
    const NAME = 'oro_website_search.event.collect_dependent_classes';

    /** @var array */
    private $dependencies = [];

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

    /**
     * @return array
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }
}

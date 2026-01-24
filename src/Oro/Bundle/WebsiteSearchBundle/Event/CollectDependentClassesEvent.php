<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched to collect entity class dependencies for website search reindexation.
 *
 * This event allows listeners to declare that certain entity classes depend on others for search indexing.
 * When an entity is reindexed, all dependent entities will also be reindexed to ensure search data consistency.
 * For example, if Product entities depend on Category entities, reindexing a Category will automatically trigger
 * reindexation of all related Products. This ensures that denormalized search data remains accurate
 * when related entities change.
 */
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
            $this->dependencies[$entityClass][$dependentEntityClass] = $dependentEntityClass;
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

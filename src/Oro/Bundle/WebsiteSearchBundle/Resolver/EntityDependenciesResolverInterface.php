<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

/**
 * Defines the contract for resolving entity class dependencies in website search reindexation.
 *
 * Implementations determine which entity classes need to be reindexed when a specific entity class is modified.
 * This is crucial for maintaining search index consistency when entities have relationships
 * that affect their search representation. For example, if Product search data includes Category information,
 * then Products must be reindexed when Categories change. The resolver uses {@see CollectDependentClassesEvent}
 * to gather dependency information from the application.
 */
interface EntityDependenciesResolverInterface
{
    /**
     * @param null|string|string[] $class
     * @return array
     */
    public function getClassesForReindex($class = null);
}

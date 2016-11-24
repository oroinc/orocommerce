<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

interface EntityDependenciesResolverInterface
{
    /**
     * @param null|string|string[] $class
     * @return array
     */
    public function getClassesForReindex($class = null);
}

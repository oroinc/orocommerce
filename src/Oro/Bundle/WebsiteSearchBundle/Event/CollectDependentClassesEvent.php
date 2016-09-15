<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectDependentClassesEvent extends Event
{
    const NAME = 'oro_website_search.event.collect_dependent_classes';

    /** @var array */
    private $dependentClasses = [];

    /** @var array */
    private $classesToResolve = [];

    /**
     * @param array $classes
     */
    public function __construct(array $classes)
    {
        $this->classesToResolve = $classes;
    }

    /**
     * @return array
     */
    public function getClassesToResolve()
    {
        return $this->classesToResolve;
    }

    /**
     * @param array $dependentClasses
     * @return $this
     */
    public function setDependentClasses(array $dependentClasses)
    {
        $this->dependentClasses = $dependentClasses;

        return $this;
    }

    /**
     * @return array
     */
    public function getDependentClasses()
    {
        return array_unique(array_merge($this->classesToResolve, $this->dependentClasses));
    }
}

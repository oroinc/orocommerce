<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that will be triggered before reindexation process.
 */
class BeforeReindexEvent extends Event
{
    const EVENT_NAME = 'oro_website_search.before_reindex';

    /**
     * @var mixed
     */
    private $classOrClasses;

    /**
     * @var array
     */
    private $context;

    /**
     * @param mixed $classOrClasses
     * @param array $context
     */
    public function __construct(
        $classOrClasses,
        array $context = []
    ) {
        $this->classOrClasses = $classOrClasses;
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getClassOrClasses()
    {
        return $this->classOrClasses;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}

<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WebsiteSearchBundle\Event\SelectDataFromSearchIndexEvent;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\SearchBundle\Query\Query;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /** @var EngineV2Interface */
    protected $engine;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param EngineV2Interface        $engine
     * @param Query                    $query
     */
    public function __construct(
        EngineV2Interface $engine,
        EventDispatcherInterface $eventDispatcher,
        Query $query
    ) {
        $this->engine     = $engine;
        $this->dispatcher = $eventDispatcher;
        $this->query      = $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function query()
    {
        // EVENT: allow additional fields to be selected
        // by custom bundles
        $event = new SelectDataFromSearchIndexEvent(
            $this->query->getSelect()
        );
        $this->dispatcher->dispatch(SelectDataFromSearchIndexEvent::EVENT_NAME, $event);
        $this->query->select($event->getSelectedData());

        return $this->engine->search($this->query);
    }
}

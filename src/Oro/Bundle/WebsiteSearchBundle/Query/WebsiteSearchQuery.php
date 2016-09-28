<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Query\AbstractSearchQuery;
use Oro\Bundle\WebsiteSearchBundle\Event\SelectDataFromSearchIndexEvent;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;

class WebsiteSearchQuery extends AbstractSearchQuery
{
    /** @var EngineV2Interface */
    protected $engine;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EngineV2Interface $engine
     * @param EventDispatcherInterface $eventDispatcher
     * @param Query $query
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
            $this->query->getSelectDataFields()
        );
        $this->dispatcher->dispatch(SelectDataFromSearchIndexEvent::EVENT_NAME, $event);
        $this->query->select($event->getSelectedData());

        return $this->engine->search($this->query);
    }
}

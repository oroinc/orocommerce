<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\LazyResult;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverAwareTrait;

/**
 * ORM website search engine
 */
class OrmEngine extends AbstractEngine
{
    use DriverAwareTrait;

    /** @var Mapper */
    private $mapper;

    public function setMapper(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @return Mapper
     * @throws \RuntimeException
     */
    protected function getMapper()
    {
        if (!$this->mapper) {
            throw new \RuntimeException('Mapper is missing');
        }

        return $this->mapper;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $context Not used here, only to comply with the interface
     */
    protected function doSearch(Query $query, array $context = [])
    {
        $elementsCallback = function () use ($query) {
            $results = [];
            $searchResults = $this->driver->search($query);

            foreach ($searchResults as $searchResult) {
                $item = $searchResult['item'];

                $results[] = new Item(
                    $item['entity'],
                    $item['recordId'],
                    null,
                    $this->mapper->mapSelectedData($query, $searchResult),
                    $this->mappingProvider->getEntityConfig($item['entity'])
                );
            }

            return $results;
        };

        $recordsCountCallback = function () use ($query) {
            return $this->driver->getRecordsCount($query);
        };

        $aggregatedDataCallback = function () use ($query) {
            return $this->driver->getAggregatedData($query);
        };

        // LazyResult is used here to do not trigger additional requests if they are not required
        return new LazyResult($query, $elementsCallback, $recordsCountCallback, $aggregatedDataCallback);
    }
}

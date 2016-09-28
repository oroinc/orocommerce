<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverAwareTrait;
use Oro\Bundle\WebsiteSearchBundle\Query\Result\Item;

class OrmEngine extends AbstractEngine
{
    use DriverAwareTrait;

    /** @var Mapper */
    private $mapper;

    /**
     * @param Mapper $mapper
     */
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
     */
    protected function doSearch(Query $query, array $context = [])
    {
        $results = [];
        $searchResults = $this->driver->search($query);

        foreach ($searchResults as $searchResult) {
            $item = $searchResult['item'];

            $results[] = new Item(
                $item['entity'],
                $item['recordId'],
                $item['title'],
                null,
                $this->mapper->mapSelectedData($query, $searchResult),
                $this->mappingProvider->getEntityConfig($item['entity'])
            );
        }

        return new Result($query, $results, count($searchResults));
    }
}

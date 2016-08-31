<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;

class ORMEngine extends AbstractEngine
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var BaseDriver[] */
    protected $drivers = [];

    /** @var ObjectMapper */
    protected $mapper;

    /** @var WebsiteSearchIndexRepository */
    protected $indexRepository;

    /** @var OroEntityManager */
    protected $indexManager;

    /**
     * {@inheritdoc}
     */
    protected function doSearch(Query $query, array $context = [])
    {
        $results = [];

        $searchResults = $this->getIndexRepository()->search($query);

        foreach ($searchResults as $searchResult) {
            $item = $searchResult['item'];
            $results[] = new Item(
                $this->getRegistry()->getManagerForClass($item['entity']),
                $item['entity'],
                $item['id'],
                $item['title'],
                null,
                $this->getMapper()->mapSelectedData($query, $searchResult),
                $this->getMapper()->getEntityConfig($item['entity'])
            );
        }

        $recordsCount = count($searchResults);

        return [
            'results'       => $results,
            'records_count' => $recordsCount
        ];
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    protected function getIndexRepository()
    {
        if ($this->indexRepository) {
            return $this->indexRepository;
        }

        $this->indexRepository = $this->getIndexManager()->getRepository('OroWebsiteSearchBundle:Item');
        $this->indexRepository->setDriversClasses($this->getDrivers());
        $this->indexRepository->setRegistry($this->getRegistry());

        return $this->indexRepository;
    }

    /**
     * @return OroEntityManager
     */
    protected function getIndexManager()
    {
        if ($this->indexManager) {
            return $this->indexManager;
        }

        $this->indexManager = $this->getRegistry()->getManagerForClass('OroWebsiteSearchBundle:Item');

        return $this->indexManager;
    }

    /**
     * @param BaseDriver[] $drivers
     */
    public function setDrivers(array $drivers)
    {
        $this->drivers = $drivers;
    }

    /**
     * @return BaseDriver[]
     * @throws \RuntimeException
     */
    protected function getDrivers()
    {
        if (!$this->drivers) {
            throw new \RuntimeException('The required parameter was not set');
        }

        return $this->drivers;
    }

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function setRegistry(ManagerRegistry $managerRegistry)
    {
        $this->registry = $managerRegistry;
    }

    /**
     * @return ManagerRegistry
     * @throws \RuntimeException
     */
    protected function getRegistry()
    {
        if (!$this->registry) {
            throw new \RuntimeException('The required parameter was not set');
        }

        return $this->registry;
    }

    /**
     * @param ObjectMapper $mapper
     */
    public function setMapper(ObjectMapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * @return ObjectMapper
     * @throws \RuntimeException
     */
    protected function getMapper()
    {
        if (!$this->mapper) {
            throw new \RuntimeException('The required parameter was not set');
        }

        return $this->mapper;
    }
}

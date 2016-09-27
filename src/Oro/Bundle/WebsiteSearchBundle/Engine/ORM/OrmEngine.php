<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\SearchBundle\Engine\Orm\BaseDriver;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\Mapper;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class OrmEngine extends AbstractEngine
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var array */
    protected $drivers = [];

    /** @var Mapper */
    protected $mapper;

    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /** @var WebsiteSearchIndexRepository */
    protected $indexRepository;

    /** @var OroEntityManager */
    protected $indexManager;

    /** @var OptionsResolver */
    private $optionsResolver;

    /**
     * {@inheritdoc}
     */
    protected function doSearch(Query $query, array $context = [])
    {
        $results = [];
        $searchResults = $this->getIndexRepository()->search($query);

        foreach ($searchResults as $searchResult) {
            if (!isset($searchResult['item'])) {
                continue;
            }
            $item = $this->resolveItemKeys($searchResult['item']);

            $results[] = new Item(
                $this->doctrineHelper->getEntityManager($item['entity']),
                $item['entity'],
                $item['recordId'],
                $item['title'],
                null,
                $this->getMapper()->mapSelectedData($query, $searchResult),
                $this->getMappingProvider()->getEntityConfig($item['entity'])
            );
        }

        return new Result($query, $results, count($searchResults));
    }

    /**
     * @param array $item
     * @return array
     */
    private function resolveItemKeys(array $item)
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
        }
        $this->optionsResolver->setRequired(['entity', 'recordId', 'title']);
        $this->optionsResolver->setDefined(array_keys($item));
        $resolvedOptions =  $this->optionsResolver->resolve($item);
        $this->optionsResolver->clear();

        return $resolvedOptions;
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    protected function getIndexRepository()
    {
        if (!$this->indexRepository) {
            $this->indexRepository = $this->getIndexManager()->getRepository('OroWebsiteSearchBundle:Item');
            $this->indexRepository->setDriversClasses($this->getDrivers());
            $this->indexRepository->setRegistry($this->getRegistry());
        }

        return $this->indexRepository;
    }

    /**
     * @return OroEntityManager
     */
    protected function getIndexManager()
    {
        if (!$this->indexManager) {
            $this->indexManager = $this->getRegistry()->getManagerForClass('OroWebsiteSearchBundle:Item');
        }

        return $this->indexManager;
    }

    /**
     * @param array $drivers
     */
    public function setDrivers(array $drivers)
    {
        foreach ($drivers as $driver) {
            if (!is_a($driver, BaseDriver::class, true)) {
                throw new InvalidConfigurationException('Wrong driver class passed, please check configuration');
            }
        }

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
            throw new \RuntimeException('The required parameter was not set');
        }

        return $this->mapper;
    }

    /**
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function setMappingProvider(WebsiteSearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @return WebsiteSearchMappingProvider
     * @throws \RuntimeException
     */
    protected function getMappingProvider()
    {
        if (!$this->mappingProvider) {
            throw new \RuntimeException('The required parameter was not set');
        }

        return $this->mappingProvider;
    }
}

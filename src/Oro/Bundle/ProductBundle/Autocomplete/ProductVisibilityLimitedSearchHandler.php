<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;

class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var  ProductRepository */
    protected $entityRepository;

    /** @var  ProductManager */
    protected $productManager;

    /** @var  FrontendHelper */
    protected $frontendHelper;

    /** @var \Oro\Bundle\ProductBundle\Search\ProductRepository */
    protected $searchRepository;

    /**
     * @param string         $entityName
     * @param array          $properties
     * @param RequestStack   $requestStack
     * @param ProductManager $productManager
     */
    public function __construct(
        $entityName,
        array $properties,
        RequestStack $requestStack,
        ProductManager $productManager
    ) {
        $this->requestStack   = $requestStack;
        $this->productManager = $productManager;
        parent::__construct($entityName, $properties);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAllDependenciesInjected()
    {
        if (!$this->entityRepository || !$this->idFieldName) {
            throw new \RuntimeException('Search handler is not fully configured');
        }
    }

    /**
     * @param FrontendHelper $frontendHelper
     */
    public function setFrontendHelper(FrontendHelper $frontendHelper)
    {
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param \Oro\Bundle\ProductBundle\Search\ProductRepository $searchRepository
     */
    public function setSearchRepository(ProductSearchRepository $searchRepository)
    {
        $this->searchRepository = $searchRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function convertItem($item)
    {
        $result = [];

        if ($this->idFieldName) {
            $result[$this->idFieldName] = $this->getPropertyValue($this->idFieldName, $item);
        }

        foreach ($this->getProperties() as $destinationKey => $property) {
            if ($this->isItem($item)) {
                $result[$destinationKey] = $this->getSelectedData($item, $property);
                continue;
            }
            $result[$property] = $this->getPropertyValue($property, $item);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        if (!isset($this->properties['orm'])) {
            return $this->properties; // usual case
        }

        $request = $this->requestStack->getCurrentRequest();

        if (null === $this->frontendHelper || (false === $this->frontendHelper->isFrontendRequest($request))) {
            return $this->properties['orm'];
        }

        return $this->properties['search'];
    }


    /**
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }

        if (null === $this->frontendHelper || (false === $this->frontendHelper->isFrontendRequest($request))) {
            return $this->searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params);
        }

        return $this->searchEntitiesUsingIndex($search, $firstResult, $maxResults);
    }

    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @param $params
     * @return array
     */
    protected function searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params)
    {
        $queryBuilder = $this->entityRepository->getSearchQueryBuilder($search, $firstResult, $maxResults);
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);
        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }

    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @return \Oro\Bundle\SearchBundle\Query\Result\Item[]
     */
    protected function searchEntitiesUsingIndex($search, $firstResult, $maxResults)
    {
        $searchQuery = $this->searchRepository->getSearchQuery($search, $firstResult, $maxResults);
        $searchQuery->setFirstResult($firstResult);
        $searchQuery->setMaxResults($maxResults);
        $result = $searchQuery->getResult();

        return $result->getElements();
    }

    /**
     * @param Item   $item
     * @param string $property
     * @return null|string
     */
    protected function getSelectedData($item, $property)
    {
        $data = $item->getSelectedData();

        if (empty($data)) {
            return null;
        }

        foreach ($data as $key => $value) {
            if ($key === $property) {
                return (string)$value;
            }

            // support localized properties
            if (strpos($key, $property) === 0) {
                return (string)$value;
            }
        }

        return null;
    }

    /**
     * @param $object
     * @return bool
     */
    protected function isItem($object)
    {
        return is_object($object) && method_exists($object, 'getSelectedData');
    }
}

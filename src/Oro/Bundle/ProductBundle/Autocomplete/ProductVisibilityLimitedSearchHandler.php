<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Search\ProductRepository as ProductSearchRepository;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Search handler with additional check for product visibility.
 */
class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    /**
     * @var bool
     */
    private $allowConfigurableProducts = false;

    /** @var RequestStack */
    protected $requestStack;

    /** @var ProductRepository */
    protected $entityRepository;

    /** @var ProductManager */
    protected $productManager;

    /** @var FrontendHelper */
    protected $frontendHelper;

    /** @var \Oro\Bundle\ProductBundle\Search\ProductRepository */
    protected $searchRepository;

    /** @var LocalizationHelper */
    private $localizationHelper;

    /**
     * @param string $entityName
     * @param RequestStack $requestStack
     * @param ProductManager $productManager
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        $entityName,
        RequestStack $requestStack,
        ProductManager $productManager,
        LocalizationHelper $localizationHelper
    ) {
        $this->requestStack   = $requestStack;
        $this->productManager = $productManager;
        $this->localizationHelper = $localizationHelper;
        parent::__construct($entityName, ['sku', 'defaultName.string']);
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

        if ($this->isItem($item)) {
            $selectedData = $item->getSelectedData();
            if (isset($selectedData['sku'], $selectedData['name'])) {
                $result += [
                    'sku' => $selectedData['sku'],
                    'defaultName.string' => $selectedData['name'],
                ];
            }
        } elseif ($item instanceof Product) {
            $result += [
                'sku' => $item->getSku(),
                'defaultName.string' => (string) $this->localizationHelper->getLocalizedValue($item->getNames()),
            ];
        } else {
            throw new InvalidArgumentException('Given item could not be converted');
        }

        return $result;
    }

    /**
     * Enables configurable products selection.
     * In most forms configurable products require additional option selection which is not implemented yet, thus they
     * are disabled by default, but can be enabled in forms where no additional functionality for selection is needed.
     */
    public function enableConfigurableProducts(): void
    {
        $this->allowConfigurableProducts = true;
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

        if (!$this->allowConfigurableProducts) {
            $queryBuilder->andWhere($queryBuilder->expr()->neq('p.type', ':configurable_type'))
                ->setParameter('configurable_type', Product::TYPE_CONFIGURABLE);
        }

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
        $searchQuery = $this->searchRepository->getSearchQueryBySkuOrName($search, $firstResult, $maxResults);

        if (!$this->allowConfigurableProducts) {
            $searchQuery->addWhere(
                Criteria::expr()->neq('type', Product::TYPE_CONFIGURABLE)
            );
        }

        $result = $searchQuery->getResult();

        return $result->getElements();
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

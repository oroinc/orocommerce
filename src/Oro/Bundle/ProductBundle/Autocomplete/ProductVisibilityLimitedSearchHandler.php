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
 * The search handler with additional check for product visibility.
 */
class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    private array $notAllowedProductTypes = [];

    private RequestStack $requestStack;

    private ProductManager $productManager;

    private FrontendHelper $frontendHelper;

    private ProductSearchRepository $searchRepository;

    private LocalizationHelper $localizationHelper;

    /**
     * @param string                  $entityName
     * @param RequestStack            $requestStack
     * @param ProductManager          $productManager
     * @param ProductSearchRepository $searchRepository
     * @param LocalizationHelper      $localizationHelper
     * @param FrontendHelper          $frontendHelper
     */
    public function __construct(
        $entityName,
        RequestStack $requestStack,
        ProductManager $productManager,
        ProductSearchRepository $searchRepository,
        LocalizationHelper $localizationHelper,
        FrontendHelper $frontendHelper
    ) {
        parent::__construct($entityName, ['sku', 'defaultName.string']);
        $this->requestStack = $requestStack;
        $this->productManager = $productManager;
        $this->searchRepository = $searchRepository;
        $this->localizationHelper = $localizationHelper;
        $this->frontendHelper = $frontendHelper;
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

        if (is_object($item) && method_exists($item, 'getSelectedData')) {
            $selectedData = $item->getSelectedData();
            if (isset($selectedData['sku'], $selectedData['name'])) {
                $result += [
                    'sku'                => $selectedData['sku'],
                    'defaultName.string' => $selectedData['name']
                ];
            }
        } elseif ($item instanceof Product) {
            $result += [
                'sku'                => $item->getSku(),
                'defaultName.string' => (string)$this->localizationHelper->getLocalizedValue($item->getNames())
            ];
        } else {
            throw new InvalidArgumentException('Given item could not be converted');
        }

        return $result;
    }

    /**
     * In most forms configurable/kit products require additional option selection which is not implemented yet,
     * thus they are disabled, but can be enabled in forms where no additional functionality for selection
     * is needed.
     */
    public function setNotAllowedProductTypes(array $notAllowedProductTypes): void
    {
        $this->notAllowedProductTypes = $notAllowedProductTypes;
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
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }

        if (!$this->frontendHelper->isFrontendUrl($request->getPathInfo())) {
            return $this->searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params);
        }

        return $this->searchEntitiesUsingIndex($search, $firstResult, $maxResults);
    }

    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     * @param $params
     *
     * @return array
     */
    private function searchEntitiesUsingOrm($search, $firstResult, $maxResults, $params)
    {
        $queryBuilder = $this->getProductRepository()->getSearchQueryBuilder($search, $firstResult, $maxResults);
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);

        if ($this->notAllowedProductTypes) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('p.type', ':notAllowedProductTypes'))
                ->setParameter('notAllowedProductTypes', $this->notAllowedProductTypes);
        }

        return $this->aclHelper->apply($queryBuilder)->getResult();
    }

    /**
     * @param $search
     * @param $firstResult
     * @param $maxResults
     *
     * @return \Oro\Bundle\SearchBundle\Query\Result\Item[]
     */
    private function searchEntitiesUsingIndex($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        $skuList = $request->request->get('sku');
        if ($skuList) {
            $searchQuery = $this->searchRepository->getFilterSkuQuery($skuList);
        } else {
            $searchQuery = $this->searchRepository->getSearchQueryBySkuOrName($search, $firstResult, $maxResults);
        }

        if ($this->notAllowedProductTypes) {
            $searchQuery->addWhere(
                Criteria::expr()->notIn('type', $this->notAllowedProductTypes)
            );
        }

        // Add marker `autocomplete_record_id` to be able to determine query context in listeners
        $searchQuery->addSelect('integer.system_entity_id as autocomplete_record_id');

        return $searchQuery->getResult()->getElements();
    }

    private function getProductRepository(): ProductRepository
    {
        return $this->entityRepository;
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Autocomplete;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;

class ProductVisibilityLimitedSearchHandler extends SearchHandler
{
    /** @var RequestStack */
    protected $requestStack;

    /** @var  ProductRepository */
    protected $entityRepository;

    /** @var  ProductManager */
    protected $productManager;

    /**
     * @param string $entityName
     * @param array $properties
     * @param RequestStack $requestStack
     * @param ProductManager $productManager
     */
    public function __construct(
        $entityName,
        array $properties,
        RequestStack $requestStack,
        ProductManager $productManager
    ) {
        $this->requestStack = $requestStack;
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
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        $request = $this->requestStack->getCurrentRequest();
        $queryBuilder = $this->entityRepository->getSearchQueryBuilder($search, $firstResult, $maxResults);

        if (!$request || !$params = $request->get(ProductSelectType::DATA_PARAMETERS)) {
            $params = [];
        }
        $this->productManager->restrictQueryBuilder($queryBuilder, $params);

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}

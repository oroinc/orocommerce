<?php

namespace OroB2B\Bundle\ProductBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductSearchHandler extends SearchHandler
{
    /**
     * @param string $entityName
     * @param array $properties
     * @param FrontendProductListModifier $productListModifier
     */
    public function __construct($entityName, array $properties, FrontendProductListModifier $productListModifier)
    {
        $this->productListModifier = $productListModifier;
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
        $queryBuilder = $this->entityRepository->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('p.names', 'pn', 'WITH', 'pn.locale IS NULL')
            ->where($queryBuilder->expr()->like('LOWER(p.sku)', ':search'))
            ->orWhere($queryBuilder->expr()->like('LOWER(pn.string)', ':search'))
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $this->productListModifier->applyPriceListLimitations($queryBuilder);

        return $queryBuilder->getQuery()->getResult();
    }
}

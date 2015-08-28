<?php

namespace OroB2B\Bundle\PricingBundle\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductPriceListAwareSearchHandler extends SearchHandler
{
    /**
     * @var FrontendProductListModifier
     */
    protected $productListModifier;

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
     * @todo Apply ACL helper after BB-1008
     *
     * {@inheritdoc}
     */
    protected function searchEntities($search, $firstResult, $maxResults)
    {
        if (strpos($search, ';') !== false) {
            list($search, $currency) = explode(';', $search);
        }
        $currency = 'USD';

        $queryBuilder = $this->entityRepository->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('p.names', 'pn', 'WITH', $queryBuilder->expr()->isNull('pn.locale'))
            ->where($queryBuilder->expr()->like('LOWER(p.sku)', ':search'))
            ->orWhere($queryBuilder->expr()->like('LOWER(pn.string)', ':search'))
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $currency = isset($currency) ? $currency : null;

        $this->productListModifier->applyPriceListLimitations($queryBuilder, $currency);

        $query = $queryBuilder->getQuery();
        //$query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}

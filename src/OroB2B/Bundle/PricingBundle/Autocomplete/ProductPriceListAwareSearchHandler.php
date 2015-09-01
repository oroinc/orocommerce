<?php

namespace OroB2B\Bundle\PricingBundle\Autocomplete;

use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductPriceListAwareSearchHandler extends SearchHandler
{
    /**
     * @var FrontendProductListModifier
     */
    protected $productListModifier;

    /**
     * @var Request
     */
    protected $request;

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
     * @param Request $request
     * @return ProductPriceListAwareSearchHandler
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;

        return $this;
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
        $currency = null;
        if ($this->request) {
            $currency = $this->request->get('currency');
        }

        $queryBuilder = $this->entityRepository->createQueryBuilder('p');
        $queryBuilder
            ->innerJoin('p.names', 'pn', 'WITH', $queryBuilder->expr()->isNull('pn.locale'))
            ->where($queryBuilder->expr()->like('LOWER(p.sku)', ':search'))
            ->orWhere($queryBuilder->expr()->like('LOWER(pn.string)', ':search'))
            ->setParameter('search', '%' . strtolower($search) . '%')
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        $this->productListModifier->applyPriceListLimitations($queryBuilder, $currency);

        $query = $this->aclHelper->apply($queryBuilder);

        return $query->getResult();
    }
}

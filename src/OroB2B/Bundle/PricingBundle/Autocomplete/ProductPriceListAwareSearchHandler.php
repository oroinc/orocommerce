<?php

namespace OroB2B\Bundle\PricingBundle\Autocomplete;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandler;

use OroB2B\Bundle\PricingBundle\Model\FrontendProductListModifier;

class ProductPriceListAwareSearchHandler extends SearchHandler
{
    /**
     * @var FrontendProductListModifier
     */
    protected $productListModifier;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param string $entityName
     * @param array $properties
     * @param FrontendProductListModifier $productListModifier
     * @param RequestStack $requestStack
     */
    public function __construct(
        $entityName,
        array $properties,
        FrontendProductListModifier $productListModifier,
        RequestStack $requestStack
    ) {
        $this->productListModifier = $productListModifier;
        $this->requestStack = $requestStack;
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
        $currency = null;
        if ($request) {
            $currency = $request->get('currency');
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

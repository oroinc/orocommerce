<?php

namespace OroB2B\Bundle\PricingBundle\Filter;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

use OroB2B\Bundle\PricingBundle\Entity\Repository\CombinedProductPriceRepository;

class FrontendProductPriceFilter extends ProductPriceFilter
{
    /**
     * @var Registry
     */
    protected $registry;
    
    /**
     * {@inheritDoc}
     */
    protected function getPriceList()
    {
        return $this->priceListRequestHandler->getPriceListByAccount();
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data || !($ds instanceof OrmFilterDatasourceAdapter)) {
            return false;
        }

        /** @var QueryBuilder $qb */
        $qb = $ds->getQueryBuilder();

        $rootAliasCollection = $qb->getRootAliases();
        $rootAlias = reset($rootAliasCollection);
        $productPriceAlias = $ds->generateParameterName('product_price_' . $this->get('data_name'));

        $priceCondition = $this->buildRangeComparisonExpr(
            $ds,
            $data['type'],
            $productPriceAlias . '.value',
            $data['value'],
            $data['value_end']
        );

        $currencyParamName = $ds->generateParameterName('currency');
        $unitParamName = $ds->generateParameterName('unit');

        $additionalCondition = $ds->expr()->andX(
            $priceCondition,
            $ds->expr()->eq($productPriceAlias . '.priceList', $this->getPriceList()->getId()),
            $ds->expr()->eq($productPriceAlias . '.product', $rootAlias . '.id'),
            $ds->expr()->eq($productPriceAlias . '.currency', ':' . $currencyParamName),
            $ds->expr()->eq($productPriceAlias . '.unit', ':' . $unitParamName)
        );

        /** @var CombinedProductPriceRepository $repository */
        $repository = $this->registry->getManagerForClass($this->productPriceClass)
            ->getRepository($this->productPriceClass);
        $repository->restrictByPrice($qb, $productPriceAlias, $additionalCondition);

        $currency = $this->get('data_name');
        $qb->setParameter($currencyParamName, $currency)
            ->setParameter($unitParamName, $data['unit']);

        return true;
    }

    public function setRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Filter;

use Doctrine\ORM\Query\Expr\Join;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberFilter;

use OroB2B\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;

class ProductPriceFilter extends NumberFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ProductPriceFilterType::NAME;
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

        $price = $data['value'];
        $type = $data['type'];

        $this->qbPrepare($ds, $data['unit']);

        $joinAlias = $this->getJoinAlias();
        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $type,
                $joinAlias . '.value',
                $parameterName
            )
        );

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $ds->setParameter($parameterName, $price);
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getJoinAlias()
    {
        return 'product_price_' . $this->get('data_name');
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string $unit
     */
    protected function qbPrepare(OrmFilterDatasourceAdapter $ds, $unit)
    {
        $qb = $ds->getQueryBuilder();

        $rootAliasCollection = $qb->getRootAliases();
        $rootAlias = reset($rootAliasCollection);
        $joinAlias = $this->getJoinAlias();

        $currency = $this->get('data_name');

        $qb->innerJoin(
            'OroB2BPricingBundle:ProductPrice',
            $joinAlias,
            Join::WITH,
            $rootAlias . '.id = IDENTITY(' . $joinAlias . '.product)'
        );

        $this->addEqExpr($ds, $joinAlias . '.currency', $ds->generateParameterName('currency'), $currency);
        $this->addEqExpr($ds, $joinAlias . '.quantity', $ds->generateParameterName('quantity'), 1);
        $this->addEqExpr($ds, 'IDENTITY(' . $joinAlias . '.unit)', $ds->generateParameterName('unit'), $unit);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
     * @param string $parameterName
     * @param mixed $parameterValue
     */
    protected function addEqExpr(FilterDatasourceAdapterInterface $ds, $fieldName, $parameterName, $parameterValue)
    {
        $this->applyFilterToClause($ds, $ds->expr()->eq($fieldName, $parameterName, true));
        $ds->setParameter($parameterName, $parameterValue);
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        if (!is_array($data) || !array_key_exists('value', $data)) {
            return false;
        }

        $data['type'] = isset($data['type']) ? $data['type'] : null;

        if (!is_numeric($data['value'])) {
            return false;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata['unitChoices'] = $this->getForm()->createView()['unit']->vars['choices'];

        return $metadata;
    }
}

<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a product price.
 */
class ProductPriceFilter extends NumberRangeFilter
{
    /** @var UnitLabelFormatterInterface */
    protected $formatter;

    /** @var PriceListRequestHandler */
    protected $priceListRequestHandler;

    /** @var string */
    protected $productPriceClass;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        UnitLabelFormatterInterface $formatter,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        parent::__construct($factory, $util);
        $this->formatter = $formatter;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * @param string $productPriceClass
     *
     * @return $this
     */
    public function setProductPriceClass($productPriceClass)
    {
        $this->productPriceClass = $productPriceClass;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return ProductPriceFilterType::class;
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

        $productPriceAlias = $ds->generateParameterName('product_price_' . $this->get('data_name'));
        $this->qbPrepare($ds, $data['unit'], $productPriceAlias);

        QueryBuilderUtil::checkIdentifier($productPriceAlias);
        $this->applyFilterToClause(
            $ds,
            $this->buildRangeComparisonExpr(
                $ds,
                $data['type'],
                $productPriceAlias . '.value',
                $data['value'],
                $data['value_end']
            )
        );

        return true;
    }

    /**
     * @param OrmFilterDatasourceAdapter $ds
     * @param string $unit
     * @param string $productPriceAlias
     */
    protected function qbPrepare(OrmFilterDatasourceAdapter $ds, $unit, $productPriceAlias)
    {
        $qb = $ds->getQueryBuilder();

        $rootAliasCollection = $qb->getRootAliases();
        $rootAlias = reset($rootAliasCollection);

        $currency = $this->get('data_name');

        QueryBuilderUtil::checkIdentifier($productPriceAlias);
        $qb->innerJoin(
            $this->productPriceClass,
            $productPriceAlias,
            Join::WITH,
            sprintf('%s.id = IDENTITY(%s.product)', $rootAlias, $productPriceAlias)
        );

        $this->addEqExpr(
            $ds,
            $productPriceAlias . '.priceList',
            $ds->generateParameterName('priceList'),
            $this->getPriceList()
        );
        $this->addEqExpr($ds, $productPriceAlias . '.currency', $ds->generateParameterName('currency'), $currency);
        $this->addEqExpr($ds, $productPriceAlias . '.quantity', $ds->generateParameterName('quantity'), 1);
        $this->addEqExpr($ds, 'IDENTITY(' . $productPriceAlias . '.unit)', $ds->generateParameterName('unit'), $unit);
    }

    /**
     * @return PriceList|null
     */
    protected function getPriceList()
    {
        return $this->priceListRequestHandler->getPriceList();
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     * @param string                           $parameterName
     * @param mixed                            $parameterValue
     */
    protected function addEqExpr(FilterDatasourceAdapterInterface $ds, $fieldName, $parameterName, $parameterValue)
    {
        $this->applyFilterToClause($ds, $ds->expr()->eq($fieldName, $parameterName, true));
        $ds->setParameter($parameterName, $parameterValue);
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);
        if (false === $data) {
            return false;
        }

        if (empty($data['unit'])) {
            return false;
        }

        if (isset($data['value'])) {
            $data['value'] = abs($data['value']);
        }

        if (isset($data['value_end'])) {
            $data['value_end'] = abs($data['value_end']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata['unitChoices'] = [];

        $formView = $this->getFormView();
        $unitChoices = $formView['unit']->vars['choices'];
        foreach ($unitChoices as $choice) {
            $metadata['unitChoices'][] = [
                'data' => $choice->data,
                'value' => $choice->value,
                'label' => $choice->label,
                'shortLabel' => $this->formatter->format($choice->value, true),
            ];
        }

        return $metadata;
    }
}

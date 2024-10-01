<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Doctrine\ORM\Query\Expr\Join;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\NumberRangeFilter;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Form\Type\Filter\ProductPriceFilterType;
use Oro\Bundle\ProductBundle\Formatter\UnitLabelFormatterInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The filter by a product price attribute.
 */
class ProductPriceAttributeFilter extends NumberRangeFilter
{
    private const ATTRIBUTE_NAME = 'attribute_name';

    /** @var UnitLabelFormatterInterface */
    protected $formatter;

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        UnitLabelFormatterInterface $formatter
    ) {
        parent::__construct($factory, $util);
        $this->formatter = $formatter;
    }

    #[\Override]
    protected function getFormType()
    {
        return ProductPriceFilterType::class;
    }

    #[\Override]
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data || !($ds instanceof OrmFilterDatasourceAdapter)) {
            return false;
        }

        $productPriceAlias = $ds->generateParameterName(
            'product_price_attribute_' . $this->get(self::ATTRIBUTE_NAME) . '_' . $this->get('data_name')
        );
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

        $attributePlAlias = $productPriceAlias . '_' . $this->get(self::ATTRIBUTE_NAME);
        QueryBuilderUtil::checkIdentifier($productPriceAlias);
        QueryBuilderUtil::checkIdentifier($attributePlAlias);
        $qb->innerJoin(
            PriceAttributePriceList::class,
            $attributePlAlias,
            Join::WITH,
            QueryBuilderUtil::sprintf(
                "%1\$s.organization = %2\$s.organization AND %2\$s.fieldName = '%3\$s'",
                $rootAlias,
                $attributePlAlias,
                $this->get(self::ATTRIBUTE_NAME)
            )
        );
        $qb->innerJoin(
            PriceAttributeProductPrice::class,
            $productPriceAlias,
            Join::WITH,
            QueryBuilderUtil::sprintf(
                '%1$s.id = IDENTITY(%2$s.product) AND IDENTITY(%2$s.priceList) = %3$s',
                $rootAlias,
                $productPriceAlias,
                $attributePlAlias
            )
        );

        $this->addEqExpr($ds, $productPriceAlias . '.currency', $ds->generateParameterName('currency'), $currency);
        $this->addEqExpr($ds, $productPriceAlias . '.quantity', $ds->generateParameterName('quantity'), 1);
        $this->addEqExpr($ds, 'IDENTITY(' . $productPriceAlias . '.unit)', $ds->generateParameterName('unit'), $unit);
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

    #[\Override]
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

    #[\Override]
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

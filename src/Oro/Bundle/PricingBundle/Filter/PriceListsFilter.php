<?php

namespace Oro\Bundle\PricingBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

/**
 * The filter by a price list.
 */
class PriceListsFilter extends EntityFilter
{
    const RELATION_CLASS_NAME_PARAMETER = 'relation_class_name';

    public function init($name, array $params)
    {
        if (empty($params[self::RELATION_CLASS_NAME_PARAMETER])) {
            throw new InvalidArgumentException(
                sprintf('Parameter %s is required', self::RELATION_CLASS_NAME_PARAMETER)
            );
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        /** @var array $data */
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $parameterName = $ds->generateParameterName($this->getName());

        /** @var OrmFilterDatasourceAdapter $ds */
        $queryBuilder = $ds->getQueryBuilder();
        $priceList = reset($data['value']);
        $relationClass = $this->params[self::RELATION_CLASS_NAME_PARAMETER];

        /** @var PriceListToCustomerRepository|PriceListToCustomerGroupRepository $repository */
        $repository = $this->doctrine->getRepository($relationClass);
        $repository->restrictByPriceList($queryBuilder, $priceList, $parameterName);

        return true;
    }
}

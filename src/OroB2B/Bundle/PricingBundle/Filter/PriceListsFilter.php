<?php

namespace OroB2B\Bundle\PricingBundle\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\EntityFilter;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;

use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository;
use OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListToAccountRepository;

class PriceListsFilter extends EntityFilter
{
    const RELATION_CLASS_NAME_PARAMETER = 'relation_class_name';

    /**
     * @var Registry
     */
    protected $registry;

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

        /** @var PriceListToAccountRepository|PriceListToAccountGroupRepository $repository */
        $repository = $this->registry->getManagerForClass($relationClass)
            ->getRepository($relationClass);
        $repository->restrictByPriceList($queryBuilder, $priceList, $parameterName);

        return true;
    }

    /**
     * @param Registry $registry
     */
    public function setRegistry(Registry $registry)
    {
        $this->registry = $registry;
    }
}

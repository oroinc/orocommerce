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
    /** @var Registry */
    protected $registry;

    const RELATION_CLASS_NAME_PARAMETER = 'relation_class_name';
    const ENTITY_ALIAS_PARAMETER = 'entity_alias';

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        if (empty($params[self::RELATION_CLASS_NAME_PARAMETER])) {
            throw new InvalidArgumentException('Parameter '
                . self::RELATION_CLASS_NAME_PARAMETER . ' is required');
        }

        if (empty($params[self::ENTITY_ALIAS_PARAMETER])) {
            throw new InvalidArgumentException('Parameter '
                .self::ENTITY_ALIAS_PARAMETER . ' is required');
        }

        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $parameterName = $ds->generateParameterName($this->getName());

        /** @var OrmFilterDatasourceAdapter $ds */
        $queryBuilder = $ds->getQueryBuilder();
        $priceList = reset($data['value']);
        $relationClass = $this->params[self::RELATION_CLASS_NAME_PARAMETER];
        $entityAlias = $this->params[self::ENTITY_ALIAS_PARAMETER];

        /** @var PriceListToAccountRepository|PriceListToAccountGroupRepository $repository */
        $repository = $this->registry->getManagerForClass($relationClass)
            ->getRepository($relationClass);
        $repository->restrictByPriceList($queryBuilder, $priceList, $entityAlias, $parameterName);

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

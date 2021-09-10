<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class TaxCodeGridListener
{
    const DATA_NAME = 'taxCode';
    const JOIN_ALIAS = 'taxCodes';

    /** @var string */
    protected $taxCodeClass;

    /** @var string */
    protected $relatedEntityClass;

    /** @var Expr */
    protected $expressionBuilder;

    /**
     * @param string $taxCodeClass
     * @param string $relatedEntityClass
     */
    public function __construct($taxCodeClass, $relatedEntityClass)
    {
        $this->taxCodeClass = $taxCodeClass;
        $this->relatedEntityClass = $relatedEntityClass;

        $this->expressionBuilder = new Expr();
    }

    public function onBuildBefore(BuildBefore $event)
    {
        $config = $event->getConfig();

        $this->addSelect($config);
        $this->addJoin($config);
        $this->addColumn($config);
        $this->addSorter($config);
        $this->addFilter($config);
    }

    /**
     * @param DatagridConfiguration $configuration
     * @return string
     * @throws \InvalidArgumentException when a root entity not found in the grid
     */
    protected function getAlias(DatagridConfiguration $configuration)
    {
        $rootAlias = $configuration->getOrmQuery()->getRootAlias();
        if (!$rootAlias) {
            throw new \InvalidArgumentException(
                sprintf(
                    'A root entity is missing for grid "%s"',
                    $configuration->getName()
                )
            );
        }

        return $rootAlias;
    }

    /**
     * @return string
     */
    protected function getColumnLabel()
    {
        return 'oro.tax.taxcode.label';
    }

    /**
     * @return string
     */
    protected function getDataName()
    {
        return self::DATA_NAME;
    }

    /**
     * @return string
     */
    protected function getJoinAlias()
    {
        return self::JOIN_ALIAS;
    }

    protected function addSelect(DatagridConfiguration $config)
    {
        $config->getOrmQuery()->addSelect(
            sprintf('%s.code AS %s', $this->getJoinAlias(), $this->getDataName())
        );
    }

    protected function addJoin(DatagridConfiguration $config)
    {
        $config->getOrmQuery()->addLeftJoin(
            $this->getAlias($config).'.taxCode',
            $this->getJoinAlias()
        );
    }

    protected function addColumn(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(sprintf('[columns][%s]', $this->getDataName()), ['label' => $this->getColumnLabel()]);
    }

    protected function addSorter(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataName()),
            ['data_name' => $this->getDataName()]
        );
    }

    protected function addFilter(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataName()),
            [
                'type' => 'entity',
                'data_name' => $this->getAlias($config) . '.taxCode',
                'options' => [
                    'field_options' => [
                        'multiple' => false,
                        'class' => $this->taxCodeClass,
                        'choice_label' => 'code'
                    ]
                ]
            ]
        );
    }
}

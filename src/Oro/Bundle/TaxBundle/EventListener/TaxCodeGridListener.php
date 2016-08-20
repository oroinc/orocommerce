<?php

namespace Oro\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class TaxCodeGridListener
{
    const DATA_NAME = 'taxCode';
    const JOIN_ALIAS = 'taxCodes';

    /** @var string */
    protected $taxCodeClass;

    /** @var string */
    protected $relatedEntityClass;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var Expr */
    protected $expressionBuilder;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string $taxCodeClass
     * @param string $relatedEntityClass
     */
    public function __construct(DoctrineHelper $doctrineHelper, $taxCodeClass, $relatedEntityClass)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->taxCodeClass = $taxCodeClass;
        $this->relatedEntityClass = $relatedEntityClass;

        $this->expressionBuilder = new Expr();
    }

    /**
     * @param BuildBefore $event
     */
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
     * @param string $relatedEntityClass
     * @return string
     * @throws \InvalidArgumentException if there is not association
     */
    protected function getFieldName($relatedEntityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($this->taxCodeClass);

        $associations = $metadata->getAssociationsByTargetClass($relatedEntityClass);
        if (!$associations) {
            throw new \InvalidArgumentException(
                sprintf('Association for "%s" not found in "%s"', $relatedEntityClass, $this->taxCodeClass)
            );
        }

        $association = reset($associations);

        return $association['fieldName'];
    }

    /**
     * @param DatagridConfiguration $configuration
     * @return string
     * @throws \InvalidArgumentException when path [source][query][from] not found in the grid
     */
    protected function getAlias(DatagridConfiguration $configuration)
    {
        $from = $configuration->offsetGetByPath('[source][query][from]');

        if (!$from) {
            throw new \InvalidArgumentException(
                sprintf(
                    '[source][query][from] is missing for grid "%s"',
                    $configuration->getName()
                )
            );
        }

        return (string)$from[0]['alias'];
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

    /**
     * @param DatagridConfiguration $config
     */
    protected function addSelect(DatagridConfiguration $config)
    {
        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.code AS %s', $this->getJoinAlias(), $this->getDataName())]
        );
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addJoin(DatagridConfiguration $config)
    {
        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->taxCodeClass,
                    'alias' => $this->getJoinAlias(),
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$this->expressionBuilder->isMemberOf(
                        $this->getAlias($config),
                        sprintf('%s.%s', $this->getJoinAlias(), $this->getFieldName($this->relatedEntityClass))
                    ),
                ],
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addColumn(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(sprintf('[columns][%s]', $this->getDataName()), ['label' => $this->getColumnLabel()]);
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addSorter(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataName()),
            ['data_name' => $this->getDataName()]
        );
    }

    /**
     * @param DatagridConfiguration $config
     */
    protected function addFilter(DatagridConfiguration $config)
    {
        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataName()),
            ['type' => 'string', 'data_name' => $this->getDataName()]
        );
    }
}

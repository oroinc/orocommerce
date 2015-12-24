<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

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

    /**  @var DoctrineHelper */
    protected $doctrineHelper;

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
        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.code AS %s', $this->getJoinAlias(), $this->getDataName())]
        );
        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->taxCodeClass,
                    'alias' => $this->getJoinAlias(),
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$this->expressionBuilder->isMemberOf(
                        $this->getAlias($config),
                        sprintf('%s.%s', $this->getJoinAlias(), $this->getFieldName())
                    ),
                ],
            ]
        );
        $config->offsetSetByPath(sprintf('[columns][%s]', $this->getDataName()), ['label' => $this->getColumnLabel()]);
        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataName()),
            ['data_name' => $this->getDataName()]
        );
        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataName()),
            ['type' => 'string', 'data_name' => $this->getDataName()]
        );
    }

    /**
     * @return string|null null if there is not association
     */
    protected function getFieldName()
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($this->taxCodeClass);

        $associations = $metadata->getAssociationsByTargetClass($this->relatedEntityClass);
        if (!$associations) {
            throw new \InvalidArgumentException(
                sprintf('Association for "%s" not found in "%s"', $this->relatedEntityClass, $this->taxCodeClass)
            );
        }

        $association = reset($associations);

        return $association['fieldName'];
    }

    /**
     * @param DatagridConfiguration $configuration
     * @return string
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
        return 'orob2b.tax.taxcode.label';
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
}

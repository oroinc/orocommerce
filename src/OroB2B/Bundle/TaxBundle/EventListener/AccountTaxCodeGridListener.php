<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class AccountTaxCodeGridListener
{
    const DATA_NAME = 'taxCode';
    const ACCOUNT_TAX_CODE_JOIN_ALIAS = 'accountTaxCodes';
    const ACCOUNT_GROUP_TAX_CODE_JOIN_ALIAS = 'accountGroupTaxCodes';
    const ACCOUNT_DATA_NAME = 'accountTaxCode';
    const ACCOUNT_GROUP_DATA_NAME = 'accountGroupTaxCode';

    /** @var string */
    protected $accountTaxCodeClass;

    /** @var string */
    protected $relatedAccountClass;

    /** @var string */
    protected $relatedAccountGroupClass;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param string         $accountTaxCodeClass
     * @param string         $relatedAccountClass
     * @param string         $relatedAccountGroupClass
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        $accountTaxCodeClass,
        $relatedAccountClass,
        $relatedAccountGroupClass
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->accountTaxCodeClass = $accountTaxCodeClass;
        $this->relatedAccountClass = $relatedAccountClass;
        $this->relatedAccountGroupClass = $relatedAccountGroupClass;

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
            [sprintf('%s.code AS %s', $this->getAccountTaxCodeAlias(), $this->getDataAccountName())]
        );

        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.code AS %s', $this->getAccountGroupTaxCodeAlias(), $this->getDataAccountGroupName())]
        );

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->accountTaxCodeClass,
                    'alias' => $this->getAccountTaxCodeAlias(),
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$this->expressionBuilder->isMemberOf(
                        $this->getAlias($config),
                        sprintf(
                            '%s.%s',
                            $this->getAccountTaxCodeAlias(),
                            $this->getFieldName($this->relatedAccountClass)
                        )
                    ),
                ],
            ]
        );

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->accountTaxCodeClass,
                    'alias' => $this->getAccountGroupTaxCodeAlias(),
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$this->expressionBuilder->isMemberOf(
                        $this->getAlias($config) . '.group',
                        sprintf(
                            '%s.%s',
                            $this->getAccountGroupTaxCodeAlias(),
                            $this->getFieldName($this->relatedAccountGroupClass)
                        )
                    ),
                ],
            ]
        );

        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataAccountName()),
            ['label' => $this->getAccountColumnLabel()]
        );

        $config->offsetSetByPath(
            sprintf('[columns][%s]', $this->getDataAccountGroupName()),
            ['label' => $this->getAccountGroupColumnLabel(), 'renderable' => false]
        );

        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataAccountName()),
            ['data_name' => $this->getDataAccountName()]
        );

        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataAccountGroupName()),
            ['data_name' => $this->getDataAccountGroupName()]
        );

        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataAccountName()),
            ['type' => 'string', 'data_name' => $this->getDataAccountName()]
        );

        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataAccountGroupName()),
            ['type' => 'string', 'data_name' => $this->getDataAccountGroupName()]
        );
    }

    /**
     * @param string $relatedEntityClass
     * @return null|string null if there is not association
     */
    protected function getFieldName($relatedEntityClass)
    {
        $metadata = $this->doctrineHelper->getEntityMetadataForClass($this->accountTaxCodeClass);

        $associations = $metadata->getAssociationsByTargetClass($relatedEntityClass);
        if (!$associations) {
            throw new \InvalidArgumentException(
                sprintf('Association for "%s" not found in "%s"', $relatedEntityClass, $this->accountTaxCodeClass)
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
    protected function getAccountColumnLabel()
    {
        return 'orob2b.tax.taxcode.label';
    }

    /**
     * @return string
     */
    protected function getAccountGroupColumnLabel()
    {
        return 'orob2b.tax.taxcode.accountgroup.label';
    }

    /**
     * @return string
     */
    protected function getAccountTaxCodeAlias()
    {
        return self::ACCOUNT_TAX_CODE_JOIN_ALIAS;
    }

    /**
     * @return string
     */
    protected function getAccountGroupTaxCodeAlias()
    {
        return self::ACCOUNT_GROUP_TAX_CODE_JOIN_ALIAS;
    }

    /**
     * @return string
     */
    protected function getDataAccountName()
    {
        return self::ACCOUNT_DATA_NAME;
    }

    /**
     * @return string
     */
    protected function getDataAccountGroupName()
    {
        return self::ACCOUNT_GROUP_DATA_NAME;
    }
}

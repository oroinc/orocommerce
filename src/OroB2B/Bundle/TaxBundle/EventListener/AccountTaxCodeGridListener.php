<?php

namespace OroB2B\Bundle\TaxBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class AccountTaxCodeGridListener extends TaxCodeGridListener
{
    const ACCOUNT_GROUP_TAX_CODE_JOIN_ALIAS = 'accountGroupTaxCodes';
    const ACCOUNT_GROUP_DATA_NAME = 'accountGroupTaxCode';

    /** @var string */
    protected $relatedAccountGroupClass;

    /**
     * @param string $relatedAccountGroupClass
     */
    public function setRelatedAccountGroupClass($relatedAccountGroupClass)
    {
        $this->relatedAccountGroupClass = $relatedAccountGroupClass;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        parent::onBuildBefore($event);

        $config = $event->getConfig();

        $config->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.code AS %s', $this->getAccountGroupTaxCodeAlias(), $this->getDataAccountGroupName())]
        );

        $config->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->taxCodeClass,
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
            sprintf('[columns][%s]', $this->getDataAccountGroupName()),
            ['label' => $this->getAccountGroupColumnLabel(), 'renderable' => false]
        );

        $config->offsetSetByPath(
            sprintf('[sorters][columns][%s]', $this->getDataAccountGroupName()),
            ['data_name' => $this->getDataAccountGroupName()]
        );

        $config->offsetSetByPath(
            sprintf('[filters][columns][%s]', $this->getDataAccountGroupName()),
            ['type' => 'string', 'data_name' => $this->getDataAccountGroupName()]
        );
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
    protected function getAccountGroupTaxCodeAlias()
    {
        return self::ACCOUNT_GROUP_TAX_CODE_JOIN_ALIAS;
    }

    /**
     * @return string
     */
    protected function getDataAccountGroupName()
    {
        return self::ACCOUNT_GROUP_DATA_NAME;
    }
}

<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class OrderLineItemGridListener
{
    const ALIAS = 'taxValue';

    /**  @var string */
    protected $taxValueClass;

    /** @var Expr */
    protected $expressionBuilder;

    /** @var array */
    protected $fromPart;

    /** @var TaxationSettingsProvider */
    protected $taxationSettingsProvider;

    /**
     * @param TaxationSettingsProvider $taxationSettingsProvider
     * @param string $taxValueClass
     */
    public function __construct(TaxationSettingsProvider $taxationSettingsProvider, $taxValueClass)
    {
        $this->taxValueClass = $taxValueClass;

        $this->expressionBuilder = new Expr();
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->checkOnBefore($event)) {
            return;
        }

        $configuration = $event->getConfig();
        $this->prepareOnBuildBefore($configuration);
        $this->addColumn($configuration);
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBeforeFrontend(BuildBefore $event)
    {
        if (!$this->checkOnBefore($event)) {
            return;
        }

        $configuration = $event->getConfig();
        $this->prepareOnBuildBefore($configuration);
        $this->addColumnFrontend($configuration);
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    protected function prepareOnBuildBefore(DatagridConfiguration $configuration)
    {
        $this->addJoin($configuration);
        $this->addSelect($configuration);
    }

    /**
     * @param BuildBefore $event
     * @return bool
     */
    protected function checkOnBefore(BuildBefore $event)
    {
        $configuration = $event->getConfig();

        if (!$this->taxationSettingsProvider->isEnabled()) {
            return false;
        }

        $fromParts = $configuration->offsetGetByPath('[source][query][from]', []);
        $this->fromPart = reset($fromParts);

        if (!isset($this->fromPart['table'], $this->fromPart['alias'])) {
            return false;
        }

        return true;
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    protected function addJoin(DatagridConfiguration $configuration)
    {
        $configuration->offsetAddToArrayByPath(
            '[source][query][join][left]',
            [
                [
                    'join' => $this->taxValueClass,
                    'alias' => self::ALIAS,
                    'conditionType' => Expr\Join::WITH,
                    'condition' => (string)$this->expressionBuilder->andX(
                        $this->expressionBuilder->eq(
                            sprintf('%s.entityClass', self::ALIAS),
                            $this->expressionBuilder->literal($this->fromPart['table'])
                        ),
                        $this->expressionBuilder->eq(
                            sprintf('%s.entityId', self::ALIAS),
                            sprintf('%s.id', $this->fromPart['alias'])
                        )
                    ),
                ],
            ]
        );
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    protected function addSelect(DatagridConfiguration $configuration)
    {
        $configuration->offsetAddToArrayByPath('[source][query][select]', [sprintf('%s.result', self::ALIAS)]);
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    protected function addColumn(DatagridConfiguration $configuration)
    {
        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'result'),
            [
                'label' => 'orob2b.tax.result.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BTaxBundle::Order/Datagrid/column.html.twig',
            ]
        );
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    public function addColumnFrontend(DatagridConfiguration $configuration)
    {
        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceIncludingTax'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.unitPrice.includingTax.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::UNIT, ResultElement::INCLUDING_TAX),
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceExcludingTax'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.unitPrice.excludingTax.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::UNIT, ResultElement::EXCLUDING_TAX),
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceTaxAmount'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.unitPrice.taxAmount.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::UNIT, ResultElement::TAX_AMOUNT),
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalIncludingTax'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.rowTotal.includingTax.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::ROW, ResultElement::INCLUDING_TAX),
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalExcludingTax'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.rowTotal.excludingTax.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::ROW, ResultElement::EXCLUDING_TAX),
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalTaxAmount'),
            [
                'label' => 'orob2b.tax.order_item_datagrid.rowTotal.taxAmount.label',
                'frontend_type' => 'string',
                'data_name' => sprintf('[result][%s][%s]', Result::ROW, ResultElement::TAX_AMOUNT) ,
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'result'),
            [
                'label' => 'orob2b.tax.result.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BTaxBundle::Order/Datagrid/Frontend/column.html.twig',
                'renderable' => false
            ]
        );
    }
}

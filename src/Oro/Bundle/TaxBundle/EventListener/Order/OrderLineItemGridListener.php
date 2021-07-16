<?php

namespace Oro\Bundle\TaxBundle\EventListener\Order;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Adds tax information to the line items grid.
 */
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

    public function onBuildBefore(BuildBefore $event)
    {
        if (!$this->checkOnBefore($event)) {
            return;
        }

        $configuration = $event->getConfig();
        $this->addJoin($configuration);
        $this->addSelect($configuration);
        $this->addColumn($configuration);
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

        $fromPart = $configuration->getOrmQuery()->getFrom();
        $this->fromPart = reset($fromPart);

        if (!isset($this->fromPart['table'], $this->fromPart['alias'])) {
            return false;
        }

        return true;
    }

    protected function addJoin(DatagridConfiguration $configuration)
    {
        $configuration->getOrmQuery()->addLeftJoin(
            $this->taxValueClass,
            self::ALIAS,
            Expr\Join::WITH,
            (string)$this->expressionBuilder->andX(
                $this->expressionBuilder->eq(
                    sprintf('%s.entityClass', self::ALIAS),
                    $this->expressionBuilder->literal($this->fromPart['table'])
                ),
                $this->expressionBuilder->eq(
                    sprintf('%s.entityId', self::ALIAS),
                    QueryBuilderUtil::getField($this->fromPart['alias'], 'id')
                )
            )
        );
    }

    protected function addSelect(DatagridConfiguration $configuration)
    {
        $configuration->getOrmQuery()->addSelect(
            sprintf('%s.result', self::ALIAS)
        );
    }

    public function addColumn(DatagridConfiguration $configuration)
    {
        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceIncludingTax'),
            [
                'label' => 'oro.tax.order_line_item.unitPrice.includingTax.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/unitIncludingTax.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceExcludingTax'),
            [
                'label' => 'oro.tax.order_line_item.unitPrice.excludingTax.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/unitExcludingTax.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'unitPriceTaxAmount'),
            [
                'label' => 'oro.tax.order_line_item.unitPrice.taxAmount.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/unitTaxAmount.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalIncludingTax'),
            [
                'label' => 'oro.tax.order_line_item.rowTotal.includingTax.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/rowIncludingTax.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalExcludingTax'),
            [
                'label' => 'oro.tax.order_line_item.rowTotal.excludingTax.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/rowExcludingTax.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'rowTotalTaxAmount'),
            [
                'label' => 'oro.tax.order_line_item.rowTotal.taxAmount.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/Property/rowTaxAmount.html.twig',
                'renderable' => false
            ]
        );

        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'taxes'),
            [
                'label' => 'oro.tax.order_line_item.taxes.label',
                'type' => 'twig',
                'frontend_type' => 'html',
                'data_name' => 'result',
                'template' => '@OroTax/Order/Datagrid/taxes.html.twig',
                'renderable' => false
            ]
        );
    }
}

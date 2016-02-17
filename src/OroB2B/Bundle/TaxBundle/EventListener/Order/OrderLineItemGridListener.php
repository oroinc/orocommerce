<?php

namespace OroB2B\Bundle\TaxBundle\EventListener\Order;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

class OrderLineItemGridListener
{
    const ALIAS = 'taxValue';

    /**  @var string */
    protected $taxValueClass;

    /** @var Expr */
    protected $expressionBuilder;

    /** @var array */
    protected $fromPart;

    /**
     * @param string $taxValueClass
     */
    public function __construct($taxValueClass)
    {
        $this->taxValueClass = $taxValueClass;

        $this->expressionBuilder = new Expr();
    }

    /**
     * @param BuildBefore $event
     */
    public function onBuildBefore(BuildBefore $event)
    {
        $configuration = $event->getConfig();

        $fromParts = $configuration->offsetGetByPath('[source][query][from]');
        $this->fromPart = reset($fromParts);

        if (!$this->fromPart) {
            return;
        }

        $this->addJoin($configuration);
        $this->addSelect($configuration);
        $this->addColumn($configuration);
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
        $configuration->offsetAddToArrayByPath(
            '[source][query][select]',
            [sprintf('%s.result', self::ALIAS)]
        );
    }

    /**
     * @param DatagridConfiguration $configuration
     */
    protected function addColumn(DatagridConfiguration $configuration)
    {
        $configuration->offsetSetByPath(
            sprintf('[columns][%s]', 'result'),
            [
                'type' => 'twig',
                'frontend_type' => 'html',
                'template' => 'OroB2BTaxBundle::column.html.twig',
            ]
        );
    }
}

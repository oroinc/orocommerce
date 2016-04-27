<?php

namespace OroB2B\Bundle\ShoppingListBundle\EventListener;

use Doctrine\ORM\Query\Expr;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendProductDatagridListener
{
    const COLUMN_LINE_ITEMS = 'current_shopping_list_line_items';

    const BLOCK_SEPARATOR = '{blk}';
    const DATA_SEPARATOR = '{unt}';

    /** @var ShoppingListManager */
    protected $shoppingListManager;

    /** @var string */
    protected $lineItemClassName;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @param string $lineItemClassName
     */
    public function __construct(ShoppingListManager $shoppingListManager, $lineItemClassName)
    {
        $this->shoppingListManager = $shoppingListManager;
        $this->lineItemClassName = $lineItemClassName;
    }

    /**
     * @param PreBuild $event
     */
    public function onPreBuild(PreBuild $event)
    {
        $config = $event->getConfig();

        $this->applyUpdatesToConfig(
            $config,
            [
                '[source][query][select]' => [
                    sprintf(
                        'GROUP_CONCAT(CONCAT(IDENTITY(shoppingListLineItem.shoppingList), %s, ' .
                        'IDENTITY(shoppingListLineItem.unit), %s, shoppingListLineItem.quantity) SEPARATOR %s) as %s',
                        (new Expr())->literal(self::BLOCK_SEPARATOR),
                        (new Expr())->literal(self::BLOCK_SEPARATOR),
                        (new Expr())->literal(self::DATA_SEPARATOR),
                        self::COLUMN_LINE_ITEMS
                    )
                ],
                '[source][query][join][left]' => [
                    [
                        'join' => $this->lineItemClassName,
                        'alias' => 'shoppingListLineItem',
                        'conditionType' => Expr\Join::WITH,
                        'condition' => 'product.id = IDENTITY(shoppingListLineItem.product)'
                    ]
                ],
                '[properties]' => [
                    self::COLUMN_LINE_ITEMS => [
                        'type' => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ]
                ]
            ]
        );
    }

    /**
     * @param DatagridConfiguration $config
     * @param array $updates
     */
    protected function applyUpdatesToConfig(DatagridConfiguration $config, array $updates)
    {
        foreach ($updates as $path => $update) {
            $config->offsetAddToArrayByPath($path, $update);
        }
    }

    /**
     * @param OrmResultAfter $event
     */
    public function onResultAfter(OrmResultAfter $event)
    {
        /** @var ResultRecord[] $records */
        $records = $event->getRecords();

        $shoppingList = $this->shoppingListManager->getCurrent();

        // handle line item units
        foreach ($records as $record) {
            $units = [];

            if ($shoppingList) {
                $concatenatedUnits = $record->getValue(self::COLUMN_LINE_ITEMS);
                if ($concatenatedUnits) {
                    $concatenatedUnits = array_map(
                        function ($unit) {
                            return explode(self::BLOCK_SEPARATOR, $unit);
                        },
                        explode(self::DATA_SEPARATOR, $concatenatedUnits)
                    );

                    foreach ($concatenatedUnits as $unit) {
                        if ((int)$unit[0] !== $shoppingList->getId()) {
                            continue;
                        }

                        $units[$unit[1]] = $unit[2];
                    }
                }
            }

            $record->addData([self::COLUMN_LINE_ITEMS => $units]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

/**
 * Replace select and andWhere part from source grid configuration
 */
class SavedForLaterLineItemsGridListener
{
    public const string REPLACED_AND_WHERE_CONDITION = 'IDENTITY(lineItem.shoppingList) = :shopping_list_id';
    public const string REPLACED_SELECT_EXPRESSION = 'IDENTITY(lineItem.shoppingList) as shoppingListId';
    public const string AND_WHERE_CONDITION = 'IDENTITY(lineItem.savedForLaterList) = :shopping_list_id';
    public const string SELECT_EXPRESSION = 'IDENTITY(lineItem.savedForLaterList) as shoppingListId';

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();
        if (!$config->getOrmQuery()) {
            return;
        }

        $this->replaceSelect($config);
        $this->replaceAddWhere($config);
    }

    private function replaceAddWhere(DatagridConfiguration $config): void
    {
        $andWhereConfigs = $config->offsetGetByPath(OrmQueryConfiguration::WHERE_AND_PATH, []);
        foreach ($andWhereConfigs as $key => $condition) {
            if ($condition !== self::REPLACED_AND_WHERE_CONDITION) {
                continue;
            }

            $config->offsetSetByPath(
                \sprintf('%s[%s]', OrmQueryConfiguration::WHERE_AND_PATH, $key),
                self::AND_WHERE_CONDITION
            );
        }
    }

    private function replaceSelect(DatagridConfiguration $config): void
    {
        $selectConfigs = $config->offsetGetByPath(OrmQueryConfiguration::SELECT_PATH, []);
        foreach ($selectConfigs as $key => $expression) {
            if ($expression !== self::REPLACED_SELECT_EXPRESSION) {
                continue;
            }

            $config->offsetSetByPath(
                \sprintf('%s[%s]', OrmQueryConfiguration::SELECT_PATH, $key),
                self::SELECT_EXPRESSION
            );
        }
    }
}

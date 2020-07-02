<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Adds price attribute columns, sorters, filters for each currency enabled in current price list.
 */
class MyShoppingListDatagridExtension extends AbstractExtension
{
    /** @var string */
    private const SUPPORTED_GRID = 'my-shopping-list-line-items-grid';

    /** @var ManagerRegistry */
    private $registry;

    /** @var ConfigManager */
    private $configManager;

    /** @var array */
    private $cache = [];

    /**
     * @param ManagerRegistry $registry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $registry, ConfigManager $configManager)
    {
        $this->registry = $registry;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return static::SUPPORTED_GRID === $config->getName() && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $value = $this->configManager->get('oro_shopping_list.my_shopping_lists_all_page_value');
        if ($this->getLineItemsCount($shoppingListId) > $value) {
            return;
        }

        $items = $config->offsetGetByPath('[options][toolbarOptions][pageSize][items]');
        $items[] = [
            'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
            'size' => $value
        ];

        $config->offsetSetByPath('[options][toolbarOptions][pageSize][items]', $items);
    }

    /**
     * {@inheritdoc}
     */
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $data->offsetSetByPath('[hasEmptyMatrix]', $this->hasEmptyMatrix($shoppingListId));
    }

    /**
     * {@inheritdoc}
     */
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $result->offsetAddToArrayByPath('[metadata]', ['hasEmptyMatrix' => $this->hasEmptyMatrix($shoppingListId)]);
    }

    /**
     * @return int
     */
    private function getShoppingListId(): int
    {
        return (int) $this->parameters->get('shopping_list_id');
    }

    /**
     * @param int $shoppingListId
     * @return bool
     */
    private function hasEmptyMatrix(int $shoppingListId): bool
    {
        if (!isset($this->cache['hasEmptyMatrix'][$shoppingListId])) {
            $this->cache['hasEmptyMatrix'][$shoppingListId] = $this->registry
                ->getManagerForClass(LineItem::class)
                ->getRepository(LineItem::class)
                ->hasEmptyMatrix($shoppingListId);
        }

        return $this->cache['hasEmptyMatrix'][$shoppingListId];
    }

    private function getLineItemsCount(int $shoppingListId): int
    {
        if (!isset($this->cache['lineItemsCount'][$shoppingListId])) {
            $shoppingList = $this->registry
                ->getManagerForClass(ShoppingList::class)
                ->getRepository(ShoppingList::class)
                ->find($shoppingListId);

            $this->cache['lineItemsCount'][$shoppingListId] = $shoppingList ? $shoppingList->getLineItemsCount() : 0;
        }

        return $this->cache['lineItemsCount'][$shoppingListId];
    }
}

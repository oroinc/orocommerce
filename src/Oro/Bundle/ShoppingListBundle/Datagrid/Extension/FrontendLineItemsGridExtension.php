<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Adds additional configuration and metadata to the grid.
 */
class FrontendLineItemsGridExtension extends AbstractExtension
{
    /** @var string[] */
    private const SUPPORTED_GRIDS = [
        'frontend-customer-user-shopping-list-grid',
        'frontend-customer-user-shopping-list-edit-grid',
    ];

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
        return \in_array($config->getName(), static::SUPPORTED_GRIDS, true) && parent::isApplicable($config);
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        $queryPart = 'lineItem.id';
        if ($this->isLineItemsGrouped()) {
            $queryPart = '(SELECT GROUP_CONCAT(innerItem.id) ' .
                'FROM Oro\Bundle\ShoppingListBundle\Entity\LineItem innerItem ' .
                'WHERE (innerItem.parentProduct = lineItem.parentProduct OR innerItem.product = lineItem.product) ' .
                'AND innerItem.shoppingList = lineItem.shoppingList ' .
                'AND innerItem.unit = lineItem.unit) as allLineItemsIds';
        }
        $config->offsetAddToArrayByPath(OrmQueryConfiguration::SELECT_PATH, [$queryPart]);

        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $shoppingList = $this->getShoppingList($shoppingListId);
        $lineItemsCount = $shoppingList ? $shoppingList->getLineItemsCount() : 0;
        $item = $this->configManager->get('oro_shopping_list.my_shopping_lists_max_line_items_per_page');
        if ($lineItemsCount <= $item) {
            $item = [
                'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                'size' => $item
            ];
        }

        $config->offsetSetByPath(
            '[options][toolbarOptions][pageSize][items]',
            array_merge(
                $config->offsetGetByPath('[options][toolbarOptions][pageSize][items]'),
                [$item]
            )
        );
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
        $data->offsetSetByPath('[canBeGrouped]', $this->canBeGrouped($shoppingListId));
        $data->offsetSetByPath('[shoppingListLabel]', $this->getShoppingList($shoppingListId)->getLabel());
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

        $result->offsetAddToArrayByPath(
            '[metadata]',
            [
                'hasEmptyMatrix' => $this->hasEmptyMatrix($shoppingListId),
                'canBeGrouped' => $this->canBeGrouped($shoppingListId),
            ]
        );
    }

    /**
     * @return int
     */
    private function getShoppingListId(): int
    {
        return (int) $this->parameters->get('shopping_list_id');
    }

    /**
     * @return bool
     */
    private function isLineItemsGrouped(): bool
    {
        $parameters = $this->parameters->get('_parameters', []);

        return $parameters['group'] ?? false;
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

    /**
     * @param int $shoppingListId
     * @return bool
     */
    private function canBeGrouped(int $shoppingListId): bool
    {
        if (!isset($this->cache['canBeGrouped'][$shoppingListId])) {
            $this->cache['canBeGrouped'][$shoppingListId] = $this->registry
                ->getManagerForClass(LineItem::class)
                ->getRepository(LineItem::class)
                ->canBeGrouped($shoppingListId);
        }

        return $this->cache['canBeGrouped'][$shoppingListId];
    }

    /**
     * @param int $shoppingListId
     * @return ShoppingList|null
     */
    private function getShoppingList(int $shoppingListId)
    {
        if (!isset($this->cache['shoppingLists'][$shoppingListId])) {
            $this->cache['shoppingLists'][$shoppingListId] = $this->registry
                ->getManagerForClass(ShoppingList::class)
                ->getRepository(ShoppingList::class)
                ->find($shoppingListId);
        }

        return $this->cache['shoppingLists'][$shoppingListId];
    }
}

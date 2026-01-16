<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Adds additional configuration and metadata to the grid.
 */
class FrontendLineItemsGridExtension extends AbstractExtension
{
    private ManagerRegistry $registry;
    private ConfigManager $configManager;
    private TokenAccessorInterface $tokenAccessor;

    private array $cache = [];
    private array $supportedGrids = [];
    private array $savedForLaterGrids = [];

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->tokenAccessor = $tokenAccessor;
    }

    public function setSupportedGrids(array $supportedGrids): void
    {
        $this->supportedGrids = $supportedGrids;
    }

    public function setSavedForLaterGrids(array $savedForLaterGrids): void
    {
        $this->savedForLaterGrids = $savedForLaterGrids;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), $this->supportedGrids, true) && parent::isApplicable($config);
    }

    #[\Override]
    public function setParameters(ParameterBag $parameters): void
    {
        if ($parameters->has(ParameterBag::MINIFIED_PARAMETERS)) {
            $minifiedParameters = $parameters->get(ParameterBag::MINIFIED_PARAMETERS);
            $additional = $parameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

            if (array_key_exists('g', $minifiedParameters)) {
                $additional['group'] = $minifiedParameters['g']['group'] ?? false;
            }

            $parameters->set(ParameterBag::ADDITIONAL_PARAMETERS, $additional);
        }

        parent::setParameters($parameters);
    }

    #[\Override]
    public function processConfigs(DatagridConfiguration $config): void
    {
        $config->offsetAddToArrayByPath(OrmQueryConfiguration::SELECT_PATH, $this->buildSelectPath($config));

        if (
            !$this->tokenAccessor->hasUser() ||
            $this->configManager->get('oro_shopping_list.shopping_list_limit') === 1
        ) {
            $config->offsetUnsetByPath('[mass_actions][move]');
        }

        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $lineItemsCount = $this->getLineItemCount($shoppingListId, $this->isSavedForLaterGrid($config));
        $item = $this->configManager->get('oro_shopping_list.shopping_lists_max_line_items_per_page');
        if ($lineItemsCount <= $item) {
            $item = [
                'label' => 'oro.shoppinglist.datagrid.toolbar.pageSize.all.label',
                'size' => $item
            ];
        }

        $config->offsetSetByPath(
            '[options][toolbarOptions][pageSize][items]',
            \array_merge(
                $config->offsetGetByPath('[options][toolbarOptions][pageSize][items]'),
                [$item]
            )
        );
    }

    #[\Override]
    public function visitMetadata(DatagridConfiguration $config, MetadataObject $data): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $data->offsetSetByPath('[hasEmptyMatrix]', $this->hasEmptyMatrix($shoppingListId));
        $data->offsetSetByPath(
            '[canBeGrouped]',
            $this->canBeGrouped($shoppingListId, $this->isSavedForLaterGrid($config))
        );
        $data->offsetSetByPath('[shoppingListLabel]', $this->getShoppingList($shoppingListId)->getLabel());
        $data->offsetAddToArrayByPath('[initialState][parameters]', ['group' => false]);
        $data->offsetAddToArrayByPath('[state][parameters]', ['group' => $this->isLineItemsGrouped()]);
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $result->offsetAddToArrayByPath(
            '[metadata]',
            [
                'hasEmptyMatrix' => $this->hasEmptyMatrix($shoppingListId, $this->isSavedForLaterGrid($config)),
                'canBeGrouped' => $this->canBeGrouped($shoppingListId, $this->isSavedForLaterGrid($config)),
            ]
        );
    }

    private function getShoppingListId(): int
    {
        return (int) $this->parameters->get('shopping_list_id');
    }

    private function isLineItemsGrouped(): bool
    {
        $parameters = $this->parameters->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

    private function hasEmptyMatrix(int $shoppingListId, bool $savedForLater = false): bool
    {
        if (!isset($this->cache['hasEmptyMatrix'][$shoppingListId])) {
            $this->cache['hasEmptyMatrix'][$shoppingListId] = $this->registry
                ->getRepository(LineItem::class)
                ->hasEmptyMatrix($shoppingListId, $savedForLater);
        }

        return $this->cache['hasEmptyMatrix'][$shoppingListId];
    }

    private function canBeGrouped(int $shoppingListId, bool $savedForLater = false): bool
    {
        if (!isset($this->cache['canBeGrouped'][$shoppingListId])) {
            $this->cache['canBeGrouped'][$shoppingListId] = $this->registry
                ->getRepository(LineItem::class)
                ->canBeGrouped($shoppingListId, $savedForLater);
        }

        return $this->cache['canBeGrouped'][$shoppingListId];
    }

    private function isSavedForLaterGrid(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), $this->savedForLaterGrids, true);
    }

    private function getShoppingList(int $shoppingListId): ?ShoppingList
    {
        if (!isset($this->cache['shoppingLists'][$shoppingListId])) {
            $this->cache['shoppingLists'][$shoppingListId] = $this->registry
                ->getRepository(ShoppingList::class)
                ->find($shoppingListId);
        }

        return $this->cache['shoppingLists'][$shoppingListId];
    }

    private function buildSelectPath(DatagridConfiguration $config): array
    {
        if ($this->isLineItemsGrouped()) {
            if ($this->isSavedForLaterGrid($config)) {
                $andWhereExpression = 'AND innerItem.savedForLaterList = lineItem.savedForLaterList ';
            } else {
                $andWhereExpression = 'AND innerItem.shoppingList = lineItem.shoppingList ';
            }

            $queryParts[] = '(SELECT GROUP_CONCAT(innerItem.id ORDER BY innerItem.id ASC) ' .
                'FROM Oro\Bundle\ShoppingListBundle\Entity\LineItem innerItem ' .
                'WHERE (' .
                '  innerItem.parentProduct = lineItem.parentProduct OR ' .
                '  (innerItem.product = lineItem.product AND innerItem.checksum = lineItem.checksum)' .
                ') ' . $andWhereExpression .
                'AND innerItem.unit = lineItem.unit) as allLineItemsIds';
            $queryParts[] = 'GROUP_CONCAT(' .
                '  COALESCE(CONCAT(parentProduct.sku, \':\', product.sku), product.sku)' .
                ') as sortSku';
        } else {
            $queryParts[] = 'lineItem.id';
            $queryParts[] = 'product.sku as sortSku';
        }

        return $queryParts;
    }

    private function getLineItemCount(int $shoppingListId, bool $savedForLater = false): int
    {
        $shoppingList = $this->getShoppingList($shoppingListId);
        if (!$shoppingList) {
            return 0;
        }

        return $savedForLater ?
            $shoppingList->getSavedForLaterLineItems()->count() :
            $shoppingList->getLineItems()->count();
    }
}

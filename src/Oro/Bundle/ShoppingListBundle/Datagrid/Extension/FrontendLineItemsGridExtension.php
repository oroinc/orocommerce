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
    /** @var string[] */
    private const SUPPORTED_GRIDS = [
        'frontend-customer-user-shopping-list-grid',
        'frontend-customer-user-shopping-list-edit-grid',
    ];

    /** @var ManagerRegistry */
    private $registry;

    /** @var ConfigManager */
    private $configManager;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var array */
    private $cache = [];

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        TokenAccessorInterface $tokenAccessor
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->tokenAccessor = $tokenAccessor;
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

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        if ($this->isLineItemsGrouped()) {
            $queryParts[] = '(SELECT GROUP_CONCAT(innerItem.id ORDER BY innerItem.id ASC) ' .
                'FROM Oro\Bundle\ShoppingListBundle\Entity\LineItem innerItem ' .
                'WHERE (innerItem.parentProduct = lineItem.parentProduct OR innerItem.product = lineItem.product) ' .
                'AND innerItem.shoppingList = lineItem.shoppingList ' .
                'AND innerItem.unit = lineItem.unit) as allLineItemsIds';
            $queryParts[] = 'GROUP_CONCAT(' .
                'COALESCE(CONCAT(parentProduct.sku, \':\', product.sku), product.sku)' .
                ') as sortSku';
        } else {
            $queryParts[] = 'lineItem.id';
            $queryParts[] = 'product.sku as sortSku';
        }
        $config->offsetAddToArrayByPath(OrmQueryConfiguration::SELECT_PATH, $queryParts);

        if (!$this->tokenAccessor->hasUser() ||
            $this->configManager->get('oro_shopping_list.shopping_list_limit') === 1
        ) {
            $config->offsetUnsetByPath('[mass_actions][move]');
        }

        $shoppingListId = $this->getShoppingListId();
        if (!$shoppingListId) {
            return;
        }

        $shoppingList = $this->getShoppingList($shoppingListId);
        $lineItemsCount = $shoppingList ? count($shoppingList->getLineItems()) : 0;
        $item = $this->configManager->get('oro_shopping_list.shopping_lists_max_line_items_per_page');
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
        $data->offsetAddToArrayByPath('[initialState][parameters]', ['group' => false]);
        $data->offsetAddToArrayByPath('[state][parameters]', ['group' => $this->isLineItemsGrouped()]);
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

    private function getShoppingListId(): int
    {
        return (int) $this->parameters->get('shopping_list_id');
    }

    private function isLineItemsGrouped(): bool
    {
        $parameters = $this->parameters->get('_parameters', []);

        return isset($parameters['group']) ? filter_var($parameters['group'], FILTER_VALIDATE_BOOLEAN) : false;
    }

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

    private function getShoppingList(int $shoppingListId): ?ShoppingList
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

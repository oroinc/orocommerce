<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\MetadataObject;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Adds price attribute columns, sorters, filters for each currency enabled in current price list.
 */
class MyShoppingListDatagridExtension extends AbstractExtension
{
    /** @var string */
    private const SUPPORTED_GRID = 'my-shopping-list-line-items-grid';

    /** @var ManagerRegistry */
    private $registry;

    /** @var array */
    private $cache = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return static::SUPPORTED_GRID === $config->getName() && parent::isApplicable($config);
    }

    /**
     * @param DatagridConfiguration $config
     * @param MetadataObject $data
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
        if (!isset($this->cache[$shoppingListId])) {
            $this->cache[$shoppingListId] = $this->registry
                ->getManagerForClass(LineItem::class)
                ->getRepository(LineItem::class)
                ->hasEmptyMatrix($shoppingListId);
        }

        return $this->cache[$shoppingListId];
    }
}

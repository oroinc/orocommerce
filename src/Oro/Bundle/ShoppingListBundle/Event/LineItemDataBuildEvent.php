<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Oro\Bundle\EntityConfigBundle\Event\Event;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * This event allows to build line items data needed for view.
 */
class LineItemDataBuildEvent extends Event
{
    /** @var string */
    public const NAME = 'oro_shopping_list.line_item.data_build';

    /** @var LineItem[] */
    protected $lineItems;

    /** @var array */
    protected $context;

    /** @var array */
    protected $lineItemsData = [];

    /**
     * @param LineItem[] $lineItems
     * @param array $context
     */
    public function __construct(array $lineItems, array $context)
    {
        $this->lineItems = $lineItems;
        $this->context = $context;
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @param int $lineItemId
     * @param string $name
     * @param mixed $value
     */
    public function addDataForLineItem(int $lineItemId, string $name, $value): void
    {
        $this->lineItemsData[$lineItemId][$name] = $value;
    }

    /**
     * @param int $lineItemId
     * @param array $lineItemData
     */
    public function setDataForLineItem(int $lineItemId, array $lineItemData): void
    {
        $this->lineItemsData[$lineItemId] = $lineItemData;
    }

    /**
     * @param int $lineItemId
     * @return array
     */
    public function getDataForLineItem(int $lineItemId): array
    {
        return $this->lineItemsData[$lineItemId] ?? [];
    }
}

<?php

namespace Oro\Bundle\ShoppingListBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityConfigBundle\Event\Event;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * This event allows to manage data for LineItems. It is dispatched by grid data listener.
 */
class LineItemDataEvent extends Event
{
    /** @var string  */
    public const NAME = 'shopping_list.line_item.data';

    /** @var LineItem[] */
    protected $lineItems;

    /** @var ArrayCollection */
    protected $data;

    /**
     * @param LineItem[] $lineItems
     */
    public function __construct(array $lineItems)
    {
        $this->lineItems = $lineItems;
        $this->data = new ArrayCollection();
    }

    /**
     * @return LineItem[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @param int $id
     * @param string $name
     * @param mixed $value
     */
    public function addDataForLineItem(int $id, string $name, $value): void
    {
        $data = $this->getDataForLineItem($id);
        $data[$name] = $value;

        $this->data->set($id, $data);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getDataForLineItem(int $id): array
    {
        return $this->data->get($id) ?: [];
    }
}

<?php

namespace Oro\Bundle\ProductBundle\Event;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\EntityConfigBundle\Event\Event;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * This event allows to collect from line items the data needed for the records of datagrid.
 */
class DatagridLineItemsDataEvent extends Event
{
    /** @var string */
    public const NAME = 'oro_product.datagrid_line_items_data';

    /** @var array<int,ProductLineItemInterface> */
    protected array $lineItems;

    protected DatagridInterface $datagrid;

    protected array $context;

    /** @var array<int,array> Arbitrary arrays of line items data indexed by ID */
    protected array $lineItemsData = [];

    /**
     * @param array<int,ProductLineItemInterface> $lineItems Line items indexed by ID.
     * @param array<int,array> $lineItemsData Arbitrary arrays of line items data indexed by ID.
     * @param DatagridInterface $datagrid
     * @param array $context
     */
    public function __construct(
        array $lineItems,
        array $lineItemsData,
        DatagridInterface $datagrid,
        array $context
    ) {
        $this->lineItems = $lineItems;
        $this->lineItemsData = $lineItemsData;
        $this->datagrid = $datagrid;
        $this->context = $context;
    }

    /**
     * @return array<int,ProductLineItemInterface> Line items indexed by ID.
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    public function getDatagrid(): DatagridInterface
    {
        return $this->datagrid;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function addDataForLineItem(int $lineItemId, array $lineItemData): void
    {
        $this->lineItemsData[$lineItemId] = array_replace($this->lineItemsData[$lineItemId] ?? [], $lineItemData);
    }

    public function setDataForLineItem(int $lineItemId, array $lineItemData): void
    {
        $this->lineItemsData[$lineItemId] = $lineItemData;
    }

    public function getDataForLineItem(int $lineItemId): array
    {
        return $this->lineItemsData[$lineItemId] ?? [];
    }

    public function getDataForAllLineItems(): array
    {
        return $this->lineItemsData;
    }

    public function getName(): string
    {
        return self::NAME . '.' . $this->datagrid->getName();
    }
}

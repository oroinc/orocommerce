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

    /** @var string */
    protected $name;

    /** @var ProductLineItemInterface[] */
    protected $lineItems;

    /** @var DatagridInterface */
    protected $datagrid;

    /** @var array */
    protected $context;

    /** @var array */
    protected $lineItemsData = [];

    /**
     * @param ProductLineItemInterface[] $lineItems
     * @param DatagridInterface $datagrid
     * @param array $context
     */
    public function __construct(
        array $lineItems,
        DatagridInterface $datagrid,
        array $context
    ) {
        $this->lineItems = $lineItems;
        $this->datagrid = $datagrid;
        $this->context = $context;
    }

    /**
     * @return ProductLineItemInterface[]
     */
    public function getLineItems(): array
    {
        return $this->lineItems;
    }

    /**
     * @return DatagridInterface
     */
    public function getDatagrid(): DatagridInterface
    {
        return $this->datagrid;
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
     * @param array $lineItemData
     */
    public function addDataForLineItem(int $lineItemId, array $lineItemData): void
    {
        $this->lineItemsData[$lineItemId] = array_replace($this->lineItemsData[$lineItemId] ?? [], $lineItemData);
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

    /**
     * @return array
     */
    public function getDataForAllLineItems(): array
    {
        return $this->lineItemsData;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME . '.' . $this->datagrid->getName();
    }
}

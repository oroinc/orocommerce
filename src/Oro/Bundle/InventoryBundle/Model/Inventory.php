<?php

namespace Oro\Bundle\InventoryBundle\Model;

/**
 * Defines constants for inventory-related field names.
 *
 * This class serves as a central location for inventory field name constants used
 * throughout the inventory management system, particularly for quantity-to-order
 * constraints on products.
 */
class Inventory
{
    const FIELD_MINIMUM_QUANTITY_TO_ORDER = 'minimumQuantityToOrder';
    const FIELD_MAXIMUM_QUANTITY_TO_ORDER = 'maximumQuantityToOrder';
}

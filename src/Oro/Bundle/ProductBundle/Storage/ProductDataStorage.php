<?php

namespace Oro\Bundle\ProductBundle\Storage;

/**
 * Implementation of data storage for product
 */
class ProductDataStorage extends AbstractSessionDataStorage
{
    public const PRODUCT_DATA_KEY = 'oro_product_data';

    public const ENTITY_DATA_KEY = 'entity_data';
    public const ENTITY_ITEMS_DATA_KEY = 'entity_items_data';

    public const ADDITIONAL_DATA_KEY = 'additional_data';
    public const TRANSITION_NAME_KEY = 'transition_name';

    public const PRODUCT_SKU_KEY = 'productSku';
    public const PRODUCT_QUANTITY_KEY = 'productQuantity';
    public const PRODUCT_UNIT_KEY = 'productUnit';
    public const PRODUCT_ORGANIZATION_KEY = 'productOrganization';

    protected function getKey(): string
    {
        return self::PRODUCT_DATA_KEY;
    }
}

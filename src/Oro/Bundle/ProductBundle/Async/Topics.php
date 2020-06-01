<?php

namespace Oro\Bundle\ProductBundle\Async;

/**
 * List of available reindexation topics
 */
class Topics
{
    public const REINDEX_PRODUCT_COLLECTION_BY_SEGMENT = 'oro_product.reindex_product_collection_by_segment';
    /** @deprecated use {@see REINDEX_PRODUCTS_BY_ATTRIBUTES} instead */
    public const REINDEX_PRODUCTS_BY_ATTRIBUTE = 'oro_product.reindex_products_by_attribute';
    public const REINDEX_PRODUCTS_BY_ATTRIBUTES = 'oro_product.reindex_products_by_attributes';
}

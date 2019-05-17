<?php

namespace Oro\Bundle\WebCatalogBundle\Async;

/**
 * Web catalog message queue topics
 */
class Topics
{
    const CALCULATE_WEB_CATALOG_CACHE = 'oro.web_catalog.calculate_cache';
    const CALCULATE_CONTENT_NODE_CACHE = 'oro.web_catalog.calculate_cache.content_node';
    const CALCULATE_CONTENT_NODE_TREE_BY_SCOPE = 'oro.web_catalog.calculate_cache.content_node_tree';
    const RESOLVE_NODE_SLUGS = 'oro_web_catalog.resolve_node_slugs';
}

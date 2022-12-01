<?php

namespace Oro\Component\WebCatalog;

/**
 * Interface for the providers of each type of ContentVariants with sortable elements in WebCatalog
 */
interface SortableContentVariantProviderInterface extends ContentVariantProviderInterface
{
    /**
     * @param array $item
     * @return mixed
     */
    public function getRecordSortOrder(array $item);
}

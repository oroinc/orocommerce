<?php

namespace Oro\Component\WebCatalog;

interface SortableContentVariantProviderInterface extends ContentVariantProviderInterface
{
    /**
     * @param array $item
     * @return mixed
     */
    public function getRecordSortOrder(array $item);
}

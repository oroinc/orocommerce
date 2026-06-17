<?php

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Resolves the website IDs whose product search index must be reindexed when the given product attributes change.
 */
interface ReindexProductsByAttributesWebsiteResolverInterface
{
    /**
     * @param int[] $attributeIds IDs of the changed attributes (FieldConfigModel IDs).
     *
     * @return int[] IDs of the websites to reindex.
     */
    public function getWebsiteIdsToReindex(array $attributeIds): array;
}

<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

/**
 * Searchable attribute type that may or may not support fulltext search
 * It is used to remove fulltext behavior for some text based attribute typos
 */
interface FulltextAwareTypeInterface
{
    /**
     * Whether attribute type supports fulltext search
     */
    public function isFulltextSearchSupported(): bool;
}

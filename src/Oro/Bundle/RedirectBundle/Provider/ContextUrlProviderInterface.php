<?php

namespace Oro\Bundle\RedirectBundle\Provider;

/**
 * Defines the contract for extracting context URLs from various data sources.
 *
 * Implementations of this interface provide methods to extract or compute a URL from
 * arbitrary data objects. This is used to determine the current context URL for URL
 * generation and redirect operations.
 */
interface ContextUrlProviderInterface
{
    /**
     * @param mixed $data
     * @return string|null
     */
    public function getUrl($data);
}

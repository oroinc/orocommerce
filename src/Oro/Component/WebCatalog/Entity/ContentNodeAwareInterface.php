<?php

namespace Oro\Component\WebCatalog\Entity;

/**
 * Defines the contract for entities that are associated with a content node.
 *
 * Implementing classes represent content variants or other entities that belong to
 * a specific content node in the web catalog hierarchy.
 */
interface ContentNodeAwareInterface
{
    /**
     * @return ContentNodeInterface
     */
    public function getNode();
}

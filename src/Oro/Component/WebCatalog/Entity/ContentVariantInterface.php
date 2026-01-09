<?php

namespace Oro\Component\WebCatalog\Entity;

/**
 * Defines the contract for content variants within a content node.
 *
 * Content variants represent different versions or representations of a content node,
 * each with a specific type (e.g., product list, landing page, system page). Variants
 * allow the same content node to be rendered differently based on context or configuration.
 */
interface ContentVariantInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type);
}

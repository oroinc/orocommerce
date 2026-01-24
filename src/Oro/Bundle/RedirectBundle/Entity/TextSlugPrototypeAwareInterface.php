<?php

namespace Oro\Bundle\RedirectBundle\Entity;

/**
 * Defines the contract for entities that support text-based slug prototypes.
 *
 * Entities implementing this interface can manage a single text-based slug prototype,
 * which is used as a template for generating URL slugs. This is simpler than localized
 * slug prototypes and is suitable for single-language or non-localized slug generation.
 */
interface TextSlugPrototypeAwareInterface
{
    /**
     * @return string
     */
    public function getTextSlugPrototype();

    /**
     * @param string $textSlugPrototype
     *
     * @return $this
     */
    public function setTextSlugPrototype($textSlugPrototype);
}

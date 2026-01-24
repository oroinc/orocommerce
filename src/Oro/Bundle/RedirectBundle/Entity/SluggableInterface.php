<?php

namespace Oro\Bundle\RedirectBundle\Entity;

/**
 * Defines the contract for entities that support full slug generation and management.
 *
 * This interface combines {@see LocalizedSlugPrototypeWithRedirectAwareInterface} and {@see SlugAwareInterface}
 * to provide complete slug functionality. Entities implementing this interface support both
 * slug prototype management with redirect configuration and slug URL management, enabling
 * comprehensive SEO-friendly URL handling with multi-language support.
 */
interface SluggableInterface extends LocalizedSlugPrototypeWithRedirectAwareInterface, SlugAwareInterface
{
    /**
     * @return int
     */
    public function getId();
}

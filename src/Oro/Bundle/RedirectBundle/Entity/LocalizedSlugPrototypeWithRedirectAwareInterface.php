<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

/**
 * Defines the contract for entities that support localized slug prototypes with redirect configuration.
 *
 * This interface extends {@see LocalizedSlugPrototypeAwareInterface} to add support for managing
 * redirect creation preferences alongside localized slug prototypes. It enables entities to
 * specify whether automatic redirects should be created when slug URLs change.
 */
interface LocalizedSlugPrototypeWithRedirectAwareInterface extends LocalizedSlugPrototypeAwareInterface
{
    /**
     * @return SlugPrototypesWithRedirect
     */
    public function getSlugPrototypesWithRedirect();

    /**
     * @param SlugPrototypesWithRedirect $slugPrototypesWithRedirect
     *
     * @return $this
     */
    public function setSlugPrototypesWithRedirect(SlugPrototypesWithRedirect $slugPrototypesWithRedirect);
}

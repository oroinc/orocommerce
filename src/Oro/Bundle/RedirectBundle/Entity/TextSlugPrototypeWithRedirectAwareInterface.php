<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Oro\Bundle\RedirectBundle\Model\TextSlugPrototypeWithRedirect;

/**
 * Defines the contract for entities that support text-based slug prototypes with redirect configuration.
 *
 * This interface extends {@see TextSlugPrototypeAwareInterface} to add support for managing redirect
 * creation preferences alongside text-based slug prototypes. It enables entities to specify
 * whether automatic redirects should be created when slug URLs change.
 */
interface TextSlugPrototypeWithRedirectAwareInterface extends TextSlugPrototypeAwareInterface
{
    /**
     * @return TextSlugPrototypeWithRedirect
     */
    public function getTextSlugPrototypeWithRedirect();

    /**
     * @param TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect
     *
     * @return $this
     */
    public function setTextSlugPrototypeWithRedirect(TextSlugPrototypeWithRedirect $textSlugPrototypeWithRedirect);
}

<?php

namespace Oro\Bundle\RedirectBundle\Entity;

use Oro\Bundle\RedirectBundle\Model\SlugPrototypesWithRedirect;

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

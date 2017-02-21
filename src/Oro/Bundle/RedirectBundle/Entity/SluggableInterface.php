<?php

namespace Oro\Bundle\RedirectBundle\Entity;

interface SluggableInterface extends LocalizedSlugPrototypeWithRedirectAwareInterface, SlugAwareInterface
{
    /**
     * @return int
     */
    public function getId();
}

<?php

namespace Oro\Bundle\RedirectBundle\Entity;

interface SluggableInterface extends LocalizedSlugPrototypeAwareInterface, SlugAwareInterface
{
    /**
     * @return int
     */
    public function getId();
}

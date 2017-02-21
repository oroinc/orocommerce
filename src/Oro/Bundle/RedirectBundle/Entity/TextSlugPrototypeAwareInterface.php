<?php

namespace Oro\Bundle\RedirectBundle\Entity;

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

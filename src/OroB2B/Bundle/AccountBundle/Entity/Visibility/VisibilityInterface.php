<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

interface VisibilityInterface
{
    /**
     * @return string
     */
    public static function getDefault();

    /**
     * @param string $visibility
     */
    public function setVisibility($visibility);

    /**
     * @return string
     */
    public function getVisibility();
}

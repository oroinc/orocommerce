<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

interface VisibilityInterface
{
    /**
     * @param object|null $target
     * @return string
     */
    public static function getDefault($target = null);

    /**
     * @param string $visibility
     * @return $this
     */
    public function setVisibility($visibility);

    /**
     * @return string
     */
    public function getVisibility();

    /**
     * @param object|null $target
     * @return array
     */
    public static function getVisibilityList($target = null);
}

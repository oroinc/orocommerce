<?php

namespace OroB2B\Bundle\AccountBundle\Entity\Visibility;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

interface VisibilityInterface
{
    /**
     * @param Category|null $category
     * @return string
     */
    public static function getDefault(Category $category);

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
     * @param Category $category
     * @return array
     */
    public static function getVisibilityList(Category $category);
}

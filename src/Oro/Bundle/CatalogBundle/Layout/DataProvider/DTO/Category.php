<?php

namespace Oro\Bundle\CatalogBundle\Layout\DataProvider\DTO;

use \Oro\Bundle\CatalogBundle\Entity\Category as CategoryEntity;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Compatibility layer to pass category with filtered children and all other data accessible to view
 */
class Category
{
    /**
     * @var CategoryEntity
     */
    private $category;

    /**
     * @var Collection|Category[]
     */
    private $children;

    /**
     * @param CategoryEntity $category
     */
    public function __construct(CategoryEntity $category)
    {
        $this->category = $category;
        $this->children = new ArrayCollection();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->category->{'get' . ucfirst($name)}(...$arguments);
    }

    /**
     * @return Collection|Category[]
     */
    public function getChildCategories()
    {
        return $this->children;
    }

    /**
     * @param Category $category
     */
    public function addChildCategory(Category $category)
    {
        $this->children->add($category);
    }
}

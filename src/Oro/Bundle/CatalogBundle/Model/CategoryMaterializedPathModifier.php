<?php

namespace Oro\Bundle\CatalogBundle\Model;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\CommerceEntityBundle\Storage\ExtraActionEntityStorageInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryMaterializedPathModifier
{
    /**
     * @var ExtraActionEntityStorageInterface
     */
    protected $storage;

    /**
     * @param ExtraActionEntityStorageInterface $storage
     */
    public function __construct(ExtraActionEntityStorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @param Category $category
     * @param array $children
     */
    public function updateMaterializedPathNested(Category $category, array $children = [])
    {
        $this->calculateMaterializedPath($category);

        foreach ($children as $child) {
            $this->calculateMaterializedPath($child, true);
        }
    }

    /**
     * @param Category $category
     * @param bool $scheduleForInsert
     */
    public function calculateMaterializedPath(Category $category, $scheduleForInsert = false)
    {
        $path = (string) $category->getId();
        $parent = $category->getParentCategory();
        if ($parent && $parent->getMaterializedPath()) {
            $path = $parent->getMaterializedPath().Category::MATERIALIZED_PATH_DELIMITER.$path;
        }

        $category->setMaterializedPath($path);
        if ($scheduleForInsert) {
            $this->storage->scheduleForExtraInsert($category);
        }
    }
}

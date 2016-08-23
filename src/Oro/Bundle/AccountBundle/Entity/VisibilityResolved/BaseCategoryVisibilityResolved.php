<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @method BaseCategoryVisibilityResolved setSourceCategoryVisibility(VisibilityInterface $sourceVisibility = null)
 * @method VisibilityInterface getSourceCategoryVisibility()
 *
 * @ORM\MappedSuperclass
 */
abstract class BaseCategoryVisibilityResolved extends BaseVisibilityResolved
{
    const SOURCE_STATIC = 1;
    const SOURCE_PARENT_CATEGORY = 2;

    /**
     * @var Category
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $category;

    /**
     * @param Category $category
     */
    public function __construct(Category $category)
    {
        $this->category = $category;
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}

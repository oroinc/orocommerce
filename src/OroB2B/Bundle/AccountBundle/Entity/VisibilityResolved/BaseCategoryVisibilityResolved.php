<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\VisibilityInterface;
use OroB2B\Bundle\CatalogBundle\Entity\Category;

/**
 * @method BaseCategoryVisibilityResolved setSourceCategoryVisibility(VisibilityInterface $sourceVisibility = null)
 * @method VisibilityInterface getSourceCategoryVisibility()
 *
 * @ORM\MappedSuperclass
 */
abstract class BaseCategoryVisibilityResolved
{
    const VISIBILITY_HIDDEN = -1;
    const VISIBILITY_VISIBLE = 1;

    const SOURCE_STATIC = 1;
    const SOURCE_PARENT_CATEGORY = 2;

    /**
     * @var Category
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\CatalogBundle\Entity\Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $category;

    /**
     * @var int
     *
     * @ORM\Column(name="visibility", type="smallint", nullable=true)
     */
    protected $visibility;

    /**
     * @var int
     *
     * @ORM\Column(name="source", type="smallint", nullable=true)
     */
    protected $source;

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

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibility()
    {
        return $this->visibility;
    }

    /**
     * @return int
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param int $source
     * @return $this
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }
}

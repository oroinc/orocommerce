<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\CategoryRepository")
 * @ORM\Table(name="oro_ctgr_vsb_resolv")
 */
class CategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var CategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    protected $sourceCategoryVisibility;

    /**
     * @return CategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param CategoryVisibility|null $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(CategoryVisibility $sourceVisibility = null)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}

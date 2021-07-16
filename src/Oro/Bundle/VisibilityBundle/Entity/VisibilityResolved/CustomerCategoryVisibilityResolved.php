<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository"
 * )
 * @ORM\Table(name="oro_cus_ctgr_vsb_resolv")
 */
class CustomerCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var CustomerCategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceCategoryVisibility;

    public function __construct(Category $category, Scope $scope)
    {
        $this->scope = $scope;
        parent::__construct($category);
    }

    /**
     * @return Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @return CustomerCategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param CustomerCategoryVisibility $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(CustomerCategoryVisibility $sourceVisibility)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}

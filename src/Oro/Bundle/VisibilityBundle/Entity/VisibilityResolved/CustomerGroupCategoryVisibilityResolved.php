<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerGroupCategoryRepository;

/**
* Entity that represents Customer Group Category Visibility Resolved
*
*/
#[ORM\Entity(repositoryClass: CustomerGroupCategoryRepository::class)]
#[ORM\Table(name: 'oro_cus_grp_ctgr_vsb_resolv')]
class CustomerGroupCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    #[ORM\ManyToOne(targetEntity: CustomerGroupCategoryVisibility::class)]
    #[ORM\JoinColumn(name: 'source_category_visibility', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?CustomerGroupCategoryVisibility $sourceCategoryVisibility = null;

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
     * @return CustomerGroupCategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param CustomerGroupCategoryVisibility $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(CustomerGroupCategoryVisibility $sourceVisibility)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}

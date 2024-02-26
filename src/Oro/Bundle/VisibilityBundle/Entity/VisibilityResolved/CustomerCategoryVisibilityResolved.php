<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerCategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CustomerCategoryRepository;

/**
* Entity that represents Customer Category Visibility Resolved
*
*/
#[ORM\Entity(repositoryClass: CustomerCategoryRepository::class)]
#[ORM\Table(name: 'oro_cus_ctgr_vsb_resolv')]
class CustomerCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    #[ORM\ManyToOne(targetEntity: CustomerCategoryVisibility::class)]
    #[ORM\JoinColumn(name: 'source_category_visibility', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected ?CustomerCategoryVisibility $sourceCategoryVisibility = null;

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

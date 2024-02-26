<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CategoryVisibility;
use Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\CategoryRepository;

/**
* Entity that represents Category Visibility Resolved
*
*/
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'oro_ctgr_vsb_resolv')]
class CategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    #[ORM\ManyToOne(targetEntity: CategoryVisibility::class)]
    #[ORM\JoinColumn(
        name: 'source_category_visibility',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'CASCADE'
    )]
    protected ?CategoryVisibility $sourceCategoryVisibility = null;

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

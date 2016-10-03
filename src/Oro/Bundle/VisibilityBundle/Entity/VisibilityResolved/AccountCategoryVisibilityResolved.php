<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository"
 * )
 * @ORM\Table(name="oro_acc_ctgr_vsb_resolv")
 */
class AccountCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var AccountCategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceCategoryVisibility;

    /**
     * @param Category $category
     */
    public function __construct(Category $category)
    {
        parent::__construct($category);
    }

    /**
     * @return AccountCategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param AccountCategoryVisibility $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(AccountCategoryVisibility $sourceVisibility)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}

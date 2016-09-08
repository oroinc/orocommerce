<?php

namespace Oro\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\AccountBundle\Entity\AccountGroup;
use Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountGroupCategoryRepository"
 * )
 * @ORM\Table(name="oro_acc_grp_ctgr_vsb_resolv")
 */
class AccountGroupCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var AccountGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accountGroup;

    /**
     * @var AccountGroupCategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceCategoryVisibility;

    /**
     * @param Category $category
     * @param AccountGroup $accountGroup
     */
    public function __construct(Category $category, AccountGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;
        parent::__construct($category);
    }

    /**
     * @return AccountGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @return AccountGroupCategoryVisibility
     */
    public function getSourceCategoryVisibility()
    {
        return $this->sourceCategoryVisibility;
    }

    /**
     * @param AccountGroupCategoryVisibility $sourceVisibility
     * @return $this
     */
    public function setSourceCategoryVisibility(AccountGroupCategoryVisibility $sourceVisibility)
    {
        $this->sourceCategoryVisibility = $sourceVisibility;

        return $this;
    }
}

<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\VisibilityBundle\Entity\Account;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility;
use Oro\Bundle\CatalogBundle\Entity\Category;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountCategoryRepository"
 * )
 * @ORM\Table(name="oro_acc_ctgr_vsb_resolv")
 */
class AccountCategoryVisibilityResolved extends BaseCategoryVisibilityResolved
{
    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var AccountCategoryVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountCategoryVisibility")
     * @ORM\JoinColumn(name="source_category_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceCategoryVisibility;

    /**
     * @param Category $category
     * @param Account $account
     */
    public function __construct(Category $category, Account $account)
    {
        $this->account = $account;
        parent::__construct($category);
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
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

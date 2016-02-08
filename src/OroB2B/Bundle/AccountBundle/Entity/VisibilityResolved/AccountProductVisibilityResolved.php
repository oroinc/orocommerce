<?php

namespace OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity(
 *    repositoryClass="OroB2B\Bundle\AccountBundle\Entity\VisibilityResolved\Repository\AccountProductRepository"
 * )
 * @ORM\Table(name="orob2b_acc_prod_vsb_resolv")
 */
class AccountProductVisibilityResolved extends BaseProductVisibilityResolved
{
    const VISIBILITY_FALLBACK_TO_ALL = 2;

    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var AccountProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceProductVisibility;

    /**
     * @param Website $website
     * @param Product $product
     * @param Account $account
     */
    public function __construct(Website $website, Product $product, Account $account)
    {
        $this->account = $account;
        parent::__construct($website, $product);
    }

    /**
     * @return Account
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * @return AccountProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param AccountProductVisibility $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(AccountProductVisibility $sourceProductVisibility)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}

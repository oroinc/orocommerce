<?php

namespace Oro\Bundle\CustomerBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository\AccountGroupProductRepository"
 * )
 * @ORM\Table(name="oro_acc_grp_prod_vsb_resolv")
 */
class AccountGroupProductVisibilityResolved extends BaseProductVisibilityResolved
{
    /**
     * @var AccountGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $accountGroup;

    /**
     * @var AccountGroupProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\Visibility\AccountGroupProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceProductVisibility;

    /**
     * @param Website $website
     * @param Product $product
     * @param AccountGroup $accountGroup
     */
    public function __construct(Website $website, Product $product, AccountGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;
        parent::__construct($website, $product);
    }

    /**
     * @return AccountGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @return AccountGroupProductVisibility
     */
    public function getSourceProductVisibility()
    {
        return $this->sourceProductVisibility;
    }

    /**
     * @param AccountGroupProductVisibility $sourceProductVisibility
     * @return $this
     */
    public function setSourceProductVisibility(AccountGroupProductVisibility $sourceProductVisibility)
    {
        $this->sourceProductVisibility = $sourceProductVisibility;

        return $this;
    }
}

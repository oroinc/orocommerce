<?php

namespace Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;

/**
 * @ORM\Entity(
 *    repositoryClass="Oro\Bundle\VisibilityBundle\Entity\VisibilityResolved\Repository\AccountProductRepository"
 * )
 * @ORM\Table(name="oro_acc_prod_vsb_resolv")
 */
class AccountProductVisibilityResolved extends BaseProductVisibilityResolved
{
    const VISIBILITY_FALLBACK_TO_ALL = 2;

    /**
     * @var Account
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\AccountBundle\Entity\Account")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $account;

    /**
     * @var AccountProductVisibility
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility")
     * @ORM\JoinColumn(name="source_product_visibility", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $sourceProductVisibility;

    /**
     * @param Scope $scope
     * @param Product $product
     */
    public function __construct(Scope $scope, Product $product)
    {
        $this->account = $scope->getAccount();
        parent::__construct($scope, $product);
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

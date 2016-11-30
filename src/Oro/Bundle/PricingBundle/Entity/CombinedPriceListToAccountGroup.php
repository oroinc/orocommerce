<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\AccountGroup;

/**
 * @ORM\Table(
 *      name="oro_cmb_plist_to_acc_gr",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_cpl_to_acc_gr_ws_unq", columns={
 *              "account_group_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(
 *     repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToAccountGroupRepository"
 * )
 */
class CombinedPriceListToAccountGroup extends BaseCombinedPriceListRelation
{
    /**
     * @var AccountGroup
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $accountGroup;

    /**
     * @return AccountGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @param AccountGroup $accountGroup
     * @return $this
     */
    public function setAccountGroup(AccountGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;

        return $this;
    }
}

<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;

/**
 * @ORM\Table(
 *  name="orob2b_price_list_acc_gr_fb",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="orob2b_price_list_acc_gr_fb_unq", columns={
 *              "account_group_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="OroB2B\Bundle\PricingBundle\Entity\Repository\PriceListAccountGroupFallbackRepository")
 */
class PriceListAccountGroupFallback extends PriceListFallback
{
    const WEBSITE = 0;
    const CURRENT_ACCOUNT_GROUP_ONLY = 1;

    /** @var AccountGroup
     *
     * @ORM\ManyToOne(targetEntity="OroB2B\Bundle\AccountBundle\Entity\AccountGroup")
     * @ORM\JoinColumn(name="account_group_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     *
     * @return $this
     */
    public function setAccountGroup($accountGroup)
    {
        $this->accountGroup = $accountGroup;

        return $this;
    }
}

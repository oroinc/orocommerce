<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * @ORM\Table(
 *      name="oro_cmb_plist_to_cus_gr",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_cpl_to_cus_gr_ws_unq", columns={
 *              "customer_group_id",
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
     * @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $accountGroup;

    /**
     * @return CustomerGroup
     */
    public function getAccountGroup()
    {
        return $this->accountGroup;
    }

    /**
     * @param CustomerGroup $accountGroup
     * @return $this
     */
    public function setAccountGroup(CustomerGroup $accountGroup)
    {
        $this->accountGroup = $accountGroup;

        return $this;
    }
}

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
 *     repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository"
 * )
 */
class CombinedPriceListToCustomerGroup extends BaseCombinedPriceListRelation
{
    /**
     * @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $customerGroup;

    /**
     * @return CustomerGroup
     */
    public function getCustomerGroup()
    {
        return $this->customerGroup;
    }

    /**
     * @param CustomerGroup $customerGroup
     * @return $this
     */
    public function setCustomerGroup(CustomerGroup $customerGroup)
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }
}

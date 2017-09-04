<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroupAwareInterface;

/**
 * @ORM\Table(
 *  name="oro_price_list_cus_gr_fb",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_price_list_cus_gr_fb_unq", columns={
 *              "customer_group_id",
 *              "website_id"
 *          })
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerGroupFallbackRepository")
 */
class PriceListCustomerGroupFallback extends PriceListFallback implements CustomerGroupAwareInterface
{
    const WEBSITE = 0;
    const CURRENT_ACCOUNT_GROUP_ONLY = 1;

    /** @var CustomerGroup
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
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
     *
     * @return $this
     */
    public function setCustomerGroup(CustomerGroup $customerGroup)
    {
        $this->customerGroup = $customerGroup;

        return $this;
    }
}

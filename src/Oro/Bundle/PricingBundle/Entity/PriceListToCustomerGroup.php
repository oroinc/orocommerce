<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroupAwareInterface;

/**
 * @ORM\Table(name="oro_price_list_to_cus_group", uniqueConstraints={
 *     @ORM\UniqueConstraint(
 *          name="oro_price_list_to_cus_group_unique_key",
 *          columns={"customer_group_id", "price_list_id", "website_id"}
 *     )
 * })
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository")
 */
class PriceListToCustomerGroup extends BasePriceListRelation implements CustomerGroupAwareInterface
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

<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * @ORM\Table(name="oro_price_list_to_cus_group")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository")
 */
class PriceListToCustomerGroup extends BasePriceListRelation
{
    /**
     * @var CustomerGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE")
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

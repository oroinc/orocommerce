<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerGroupRepository;

/**
* Entity that represents Combined Price List To Customer Group
*
*/
#[ORM\Entity(repositoryClass: CombinedPriceListToCustomerGroupRepository::class)]
#[ORM\Table(name: 'oro_cmb_plist_to_cus_gr')]
#[ORM\UniqueConstraint(name: 'oro_cpl_to_cus_gr_ws_unq', columns: ['customer_group_id', 'website_id'])]
class CombinedPriceListToCustomerGroup extends BaseCombinedPriceListRelation
{
    #[ORM\ManyToOne(targetEntity: CustomerGroup::class)]
    #[ORM\JoinColumn(name: 'customer_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?CustomerGroup $customerGroup = null;

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

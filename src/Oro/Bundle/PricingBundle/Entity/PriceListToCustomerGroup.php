<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroupAwareInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerGroupRepository;

/**
* Entity that represents Price List To Customer Group
*
*/
#[ORM\Entity(repositoryClass: PriceListToCustomerGroupRepository::class)]
#[ORM\Table(name: 'oro_price_list_to_cus_group')]
#[ORM\UniqueConstraint(
    name: 'oro_price_list_to_cus_group_unique_key',
    columns: ['customer_group_id', 'price_list_id', 'website_id']
)]
class PriceListToCustomerGroup extends BasePriceListRelation implements CustomerGroupAwareInterface
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

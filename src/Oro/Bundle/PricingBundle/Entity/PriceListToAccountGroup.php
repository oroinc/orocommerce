<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;

/**
 * @ORM\Table(name="oro_price_list_to_cus_group")
 * @ORM\Entity(repositoryClass="Oro\Bundle\PricingBundle\Entity\Repository\PriceListToAccountGroupRepository")
 */
class PriceListToAccountGroup extends BasePriceListRelation
{
    /**
     * @var CustomerGroup
     *
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerGroup")
     * @ORM\JoinColumn(name="customer_group_id", referencedColumnName="id", onDelete="CASCADE")
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

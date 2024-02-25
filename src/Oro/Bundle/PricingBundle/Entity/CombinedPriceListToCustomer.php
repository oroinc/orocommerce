<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PricingBundle\Entity\Repository\CombinedPriceListToCustomerRepository;

/**
* Entity that represents Combined Price List To Customer
*
*/
#[ORM\Entity(repositoryClass: CombinedPriceListToCustomerRepository::class)]
#[ORM\Table(name: 'oro_cmb_price_list_to_cus')]
#[ORM\UniqueConstraint(name: 'oro_cpl_to_cus_ws_unq', columns: ['customer_id', 'website_id'])]
class CombinedPriceListToCustomer extends BaseCombinedPriceListRelation
{
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected ?Customer $customer = null;

    /**
     * @return Customer
     */
    public function getCustomer()
    {
        return $this->customer;
    }

    /**
     * @param Customer $customer
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }
}

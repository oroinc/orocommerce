<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListToCustomerRepository;

/**
* Entity that represents Price List To Customer
*
*/
#[ORM\Entity(repositoryClass: PriceListToCustomerRepository::class)]
#[ORM\Table(name: 'oro_price_list_to_customer')]
#[ORM\UniqueConstraint(
    name: 'oro_price_list_to_customer_unique_key',
    columns: ['customer_id', 'price_list_id', 'website_id']
)]
class PriceListToCustomer extends BasePriceListRelation implements CustomerAwareInterface
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

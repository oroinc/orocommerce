<?php

namespace Oro\Bundle\PricingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAwareInterface;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceListCustomerFallbackRepository;

/**
* Entity that represents Price List Customer Fallback
*
*/
#[ORM\Entity(repositoryClass: PriceListCustomerFallbackRepository::class)]
#[ORM\Table(name: 'oro_price_list_cus_fb')]
#[ORM\UniqueConstraint(name: 'oro_price_list_cus_fb_unq', columns: ['customer_id', 'website_id'])]
class PriceListCustomerFallback extends PriceListFallback implements CustomerAwareInterface
{
    const ACCOUNT_GROUP = 0;
    const CURRENT_ACCOUNT_ONLY = 1;

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
     *
     * @return $this
     */
    public function setCustomer(Customer $customer)
    {
        $this->customer = $customer;

        return $this;
    }
}
